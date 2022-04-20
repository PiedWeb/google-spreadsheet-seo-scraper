<?php

namespace PiedWeb\GoogleSpreadsheetSeoScraper;

use Exception;
use League\Csv\Reader;
use PiedWeb\Curl\ExtendedClient;
use PiedWeb\Google\Extractor\SERPExtractor;
use PiedWeb\Google\GoogleSERPManager;
use PiedWeb\Google\Result\OrganicResult;
use Symfony\Component\Console\Input\ArgvInput;

class GoogleSpreadsheetSeoScraper
{
    public ?ExtendedClient $client = null;

    protected ArgvInput $args;

    protected string $dir;

    /** @var array<array{'kw': string, 'tld': string, 'hl': string, 'pos':string, 'url':string, 'domain': string}> */
    protected array $kws;

    protected string $csvToReturn = '';

    /**
     * @var string[]
     */
    protected array $domain = [];

    protected bool $quiet = false;

    protected bool $failed = false;

    protected string $id;

    protected bool $previousRequestUsedCache = false;

    protected string $prevError;

    /** @var string[] */
    private array $proxies = [];

    private int $attempt = 1;

    /**
     * @param array<mixed> $argv
     */
    public function __construct(array $argv)
    {
        $this->dir = __DIR__.'/..';
        $this->id = uniqid('tmp_');
        $this->loadArgv($argv);
        $this->extractData();
    }

    /**
     * @param array<mixed> $argv
     */
    protected function loadArgv(array $argv): void
    {
        $this->args = new ArgvInput($argv);

        $ods = $this->args->getParameterOption('--ods', '');
        $retry = $this->args->hasParameterOption('--retry');
        $domain = $this->args->getParameterOption('--domain', '');

        if (('' === $ods && ! $retry) || '' === $domain) {
            throw new Exception('At least 1 parameter is missing : --ods, --retry or --domain');
        }

        if (! $retry && \is_string($ods) && ! file_exists($ods)) {
            throw new Exception('--ods path is not working'.\chr(10).\chr(10));
        }

        if ($this->args->getParameterOption('--quiet')) {
            $this->quiet = true;
        }

        if (\is_string($domain)) {
            $this->domain = explode(',', $domain);
        }

        if (\is_string($proxy = $this->args->getParameterOption('--proxy'))) {
            $this->proxies = explode(',', $proxy);
        }
    }

    public function getLastRun(): string
    {
        $dataDirectory = $this->dir.'/var';

        $files = \Safe\glob($dataDirectory.'/*');
        usort($files, function ($a, $b): int { return \intval(filemtime($a) < filemtime($b)); });

        return $files[0] ?? '';
    }

    protected function getRetryFile(): string
    {
        if ('last' === $this->args->getParameterOption('--retry')) {
            $retryFile = $this->getLastRun();
        } else {
            $retryFile = $this->dir.'/var/'.$this->args->getParameterOption('--retry').'.csv';
        }

        if (! file_exists($retryFile)) {
            throw new \Exception('Previsous session (`'.$this->args->getParameterOption('--retry').'`) not found');
        }

        return $retryFile;
    }

    private function getCsv(): Reader
    {
        if ($this->args->getParameterOption('--retry')) {
            $retryFile = $this->getRetryFile();

            return Reader::createFromPath($retryFile, 'r');
        }

        $tmpCsvDir = $this->dir.'/tmp';

        // $comand = 'unoconv -o "'.$tmpCsvFile.'" -f csv "'.$this->args->getParameterOption('--ods').'"';
        $comand = 'libreoffice --nolockcheck --convert-to csv:"Text - txt - csv (StarCalc)":44,34,76 --outdir "'.$tmpCsvDir.'" "'.$this->args->getParameterOption('--ods').'"';
        exec($comand);

        $files = \Safe\glob($tmpCsvDir.'/*');
        usort($files, function ($a, $b): int { return \intval(filemtime($a) < filemtime($b)); });
        $lastConvertedFile = $files[0];

        return Reader::createFromPath($lastConvertedFile, 'r');
    }

    protected function extractData(): void
    {
        $csv = $this->getCsv();

        $csv->setHeaderOffset(0);

        $kws = $csv->getRecords();
        foreach ($kws as $k => $kw) {
            if (! \is_array($kw) || ! \is_string($kw['kw'] ?? null) || ! \is_string($kw['tld'] ?? null) || ! \is_string($kw['hl'] ?? null) || ! \is_string($kw['pos'] ?? null) || ! \is_string($kw['url'] ?? null)) {
                throw new Exception('CSV is not containing kw,tld,hl,pos,url columns or one of them.');
            }

            $this->kws[$k] = [
                'kw' => $kw['kw'], 'tld' => $kw['tld'], 'hl' => $kw['hl'],
                'pos' => $kw['pos'], 'url' => $kw['url'], 'domain' => $kw['domain'] ?? '',
            ];
        }
    }

    /**
     * @param array{'kw': string, 'tld': string, 'hl': string, 'pos':string, 'url':string, 'domain': string} $kw
     */
    protected function addCsvRow(array $kw, string|int $pos, string $url): static
    {
        $this->csvToReturn .= '"'.$kw['kw'].'","'.$kw['tld'].'","'.$kw['hl'].'",';
        $this->csvToReturn .= '"'.$pos.'","'.$url.'"'.\chr(10);

        return $this;
    }

    protected function checkTheSerp(): void
    {
        $kwsNbr = \count($this->kws);

        $this->csvToReturn = 'kw,tld,hl,pos,url'.\chr(10);

        foreach ($this->kws as $i => $kw) {
            if ($this->failed || '' === $kw['kw']) {
                $this->addCsvRow($kw, '', '');

                continue;
            }

            // MAYBE WE ever checked the pos
            if ('' !== $kw['pos'] && 'FAILED' !== $kw['pos']) {
                $this->addCsvRow($kw, $kw['pos'], $kw['url']);
            } else {
                $this->messageForCli($kw['kw'].' ('.$kw['tld'].';'.$kw['hl'].')');

                $results = $this->getGoogleResults($kw);

                $this->parseGoogleResults($results, $kw);

                $this->messageForCli('------------');

                if ($i !== $kwsNbr && ! $this->previousRequestUsedCache) {
                    sleep(\intval($this->arg('--sleep', 60)));
                }
            }
        }

        if ($this->failed) {
            file_put_contents($this->dir.'/var/'.$this->id.'.csv', $this->csvToReturn);
        }
    }

    /**
     * @param OrganicResult[]                                                                                $results
     * @param array{'kw': string, 'tld': string, 'hl': string, 'pos':string, 'url':string, 'domain': string} $kw
     */
    protected function parseGoogleResults(array $results, array $kw, bool $retry = true): void
    {
        $result = ['pos' => '-1', 'url' => ''];

        foreach ($results as $r) {
            $host = parse_url($r->url, \PHP_URL_HOST);
            if (('' !== $kw['domain'] && $kw['domain'] == $host) || \in_array($host, $this->domain)) {
                $result = [
                    'pos' => $r->pos,
                    'url' => $r->url,
                ];

                break;
            }
        }

        if ($retry && '-1' === $result['pos'] && $this->args->hasParameterOption('--num-100')) {
            $this->parseGoogleResults($this->getGoogleResults($kw, 100), $kw, false);
        } else {
            $this->addCsvRow($kw, $result['pos'], $result['url']);
        }
    }

    protected function messageForCli(string $msg): void
    {
        if (! $this->quiet) {
            echo $msg.\chr(10);
        }
    }

    public function getClient(): ExtendedClient
    {
        if (null === $this->client) {
            $this->client = new ExtendedClient();
            $this->client
                ->setDesktopUserAgent()
                ->setDefaultSpeedOptions()
                ->setCookie('CONSENT=YES+')
                ->fakeBrowserHeader();
        }

        return $this->client;
    }

    private function requestGoogleWithCurl(GoogleSERPManager $Google): string
    {
        $this->messageForCli('Requesting Google with Curl');
        $this->getClient()->setLanguage($Google->language.';q=0.9');
        $this->manageProxy();

        $this->getClient()->request($Google->generateGoogleSearchUrl());
        if (0 !== $this->getClient()->getError()) {
            throw new Exception($this->getClient()->getErrorMessage());
        }

        return $this->getClient()->getResponse()->getBody();
    }

    private function manageProxy(): void
    {
        if ($this->args->hasParameterOption('--proxy')) {
            shuffle($this->proxies);
            $proxy = $this->proxies[0] ?? null;
            if (null === $proxy) {
                throw new Exception('Proxies are running out of stock');
            }

            $this->messageForCli('Using proxy '.$proxy.'');
            $this->getClient()->setProxy($proxy);
        }
    }

    /**
     * @param array{'kw': string, 'tld': string, 'hl': string, 'pos':string, 'url':string, 'domain': string} $kw
     *
     * @return OrganicResult[]
     */
    protected function getGoogleResults(array $kw, int $num = 10): array
    {
        $Google = new GoogleSERPManager();
        $Google->q = $kw['kw'];
        $Google->tld = '' !== $kw['tld'] ? $kw['tld'] : 'fr';
        $Google->language = '' !== $kw['hl'] ? $kw['hl'] : 'fr';
        if (10 != $num) {
            $Google->setParameter('num', 100);
        }

        $Google->generateGoogleSearchUrl();

        $this->previousRequestUsedCache = true;
        if (($rawHtml = $Google->getCache()) === null) {
            $rawHtml = $this->requestGoogleWithCurl($Google);
            $Google->setCache($rawHtml);
            $this->previousRequestUsedCache = false;
        }

        $extractor = new SERPExtractor($rawHtml);
        $result = $extractor->getOrganicResults();

        if ([] === $result) {
            $Google->deleteCache();
            if ($this->args->getParameterOption('--proxy')) {
                $this->messageForCli('Proxy `'.$this->proxies[0].'` looks like dead');
                unset($this->proxies[0]);

                return $this->getGoogleResults($kw, $num);
            }

            if (1 === $this->attempt) {
                ++$this->attempt;
                $this->messageForCli('First attempt failed...');
                $this->messageForCli('New try in '.$this->arg('--sleep', 60).' seconds...');
                sleep(\intval($this->arg('--sleep', 60)));

                return $this->getGoogleResults($kw, $num);
            }

            throw new Exception('no google result, try using a proxy or check the keyword');
        }

        return $result;
    }

    protected function arg(string $name, mixed $default): mixed
    {
        return false !== $this->args->getParameterOption($name) ? $this->args->getParameterOption($name) : $default;
    }

    public function exec(): void
    {
        $this->checkTheSerp();

        // store results and show them

        file_put_contents($this->dir.'/tmp.csv', $this->csvToReturn);

        if (! $this->failed) {
            exec('libreoffice '.$this->dir.'/tmp.csv');
        }
    }
}
