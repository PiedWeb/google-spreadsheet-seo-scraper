<?php

namespace PiedWeb\GoogleSpreadsheetSeoScraper;

use Exception;
use League\Csv\Reader;
use rOpenDev\Google\SearchViaCurl;
use Symfony\Component\Console\Input\ArgvInput;

class GoogleSpreadsheetSeoScraper
{
    /**
     * @var ArgvInput
     */
    protected $args;

    /**
     * @var string
     */
    protected $dir;

    /**
     * @var \League\Csv\MapIterator
     */
    protected $kws;

    /**
     * contain csv result data.
     *
     * @var string
     */
    protected $csvToReturn = '';

    /**
     * @var array
     */
    protected $domain = [];

    /**
     * @var
     */
    protected $quiet = false;

    protected $failed = false;

    protected $id = false;

    /**
     * @var string
     */
    protected $prevError;

    public function __construct($argv, string $dir)
    {
        $this->dir = $dir;
        $this->id = uniqid('tmp_');
        $this->loadArgv($argv);
        $this->extractData();
    }

    protected function loadArgv($argv)
    {
        $this->args = new ArgvInput($argv);

        if ((! $this->args->getParameterOption('--ods') && ! $this->args->getParameterOption('--retry'))
        || ! $this->args->getParameterOption('--domain')) {
            throw new Exception('At least 1 parameter is missing : --ods, --retry or --domain');
        }

        if (! $this->args->getParameterOption('--retry') && ! file_exists($this->args->getParameterOption('--ods'))) {
            throw new Exception('--ods path is not working'.\chr(10).\chr(10));
        }

        if ($this->args->getParameterOption('--quiet')) {
            $this->quiet = true;
        }

        $this->domain = explode(',', $this->args->getParameterOption('--domain'));
    }

    public function getLastRun(): string
    {
        $dataDirectory = $this->dir.'/var';

        $dir = scandir($dataDirectory);
        $lastRun = null;
        $lastRunAt = null;

        foreach ($dir as $file) {
            if ('.' != $file && '..' != $file && ! is_dir($dataDirectory.'/'.$file)
            && filemtime($dataDirectory.'/'.$file) > $lastRunAt) {
                $lastRun = $file;
                $lastRunAt = filemtime($dataDirectory.'/'.$file);
            }
        }

        if (null === $lastRun) {
            throw new \Exception('No previous run was found.');
        }

        return $lastRun;
    }

    protected function getRetryFile(): string
    {
        if ('last' === $this->args->getParameterOption('--retry')) {
            $retryFile = $this->dir.'/var/'.$this->getLastRun();
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

        //$comand = 'unoconv -o "'.$tmpCsvFile.'" -f csv "'.$this->args->getParameterOption('--ods').'"';
        $comand = 'libreoffice --nolockcheck --convert-to csv:"Text - txt - csv (StarCalc)":44,34,76 --outdir "'.$tmpCsvDir.'" "'.$this->args->getParameterOption('--ods').'"';
        exec($comand);
        //dd($comand);

        $files = scandir($tmpCsvDir, \SCANDIR_SORT_DESCENDING);
        $lastConvertedFile = $files[0];

        return Reader::createFromPath($tmpCsvDir.'/'.$lastConvertedFile, 'r');
    }

    protected function extractData()
    {
        $csv = $this->getCsv();

        $csv->setHeaderOffset(0);

        $this->kws = $csv->getRecords();
    }

    protected function addCsvRow($kw, $pos, $url)
    {
        $this->csvToReturn .= '"'.$kw['kw'].'","'.$kw['tld'].'","'.$kw['hl'].'",'.$kw['importance'].',';
        $this->csvToReturn .= '"'.$pos.'","'.$url.'"'.\chr(10);

        return $this;
    }

    protected function checkTheSerp()
    {
        $kwsNbr = iterator_count($this->kws);

        $this->csvToReturn = 'kw,tld,hl,importance,pos,url'.\chr(10);

        foreach ($this->kws as $i => $kw) {
            if (true === $this->failed || empty($kw['kw'])) {
                $this->addCsvRow($kw, '', '');

                continue;
            }

            if (0 == $kw['importance']) { // we don't check some keywords
                continue;
            }

            // MAYBE WE ever checked the pos
            if (isset($kw['pos']) && '' !== $kw['pos'] && 'FAILED' !== $kw['pos']) {
                $this->addCsvRow($kw, $kw['pos'], $kw['url']);
            } else {
                $this->messageForCli($kw['kw'].' ('.$kw['tld'].';'.$kw['hl'].')');

                $results = $this->getGoogleResults($kw);

                if ($results) {
                    $this->parseGoogleResults($results, $kw); // update directly CSV
                } else {
                    $this->messageForCli(
                        'Session id : '.$this->id
                        .\chr(10).'An error occured during the request to Google...'
                        .\chr(10).$this->prevError
                        .\chr(10).\chr(10)
                        .'Retry command : '
                        .\chr(10).'php scrap.php --retry '.$this->id
                        .(isset($this->domain) ? ' --domain '.$this->args->getParameterOption('--domain') : '').\chr(10)
                    );
                    $this->addCsvRow($kw, 'FAILED', '');
                    $this->failed = true;

                    continue;
                    //return; // quit if we get a captcha ?!
                }

                $this->messageForCli('------------');

                if ($i !== $kwsNbr) {
                    sleep($this->arg('--sleep', 60));
                }
            }
        }

        if (true === $this->failed) {
            file_put_contents($this->dir.'/var/'.$this->id.'.csv', $this->csvToReturn);
        }
    }

    protected function parseGoogleResults($results, $kw)
    {
        $result = ['pos' => '-1', 'url' => ''];

        foreach ($results as $k => $r) {
            $host = parse_url($r['link'], \PHP_URL_HOST);
            if ((isset($kw['domain']) && $kw['domain'] == $host)
            || \in_array($host, $this->domain)
            ) {
                $result = [
                    'pos' => $k + 1,
                    'url' => $r['link'],
                ];

                break;
            }
        }

        $this->addCsvRow($kw, $result['pos'], $result['url']);
    }

    protected function messageForCli($msg)
    {
        if (! $this->quiet) {
            echo $msg.\chr(10);
        }
    }

    protected function getGoogleResults(array $kw)
    {
        $Google = new SearchViaCurl($kw['kw']);
        $Google
            ->setTld($kw['tld'] ?? 'fr')
            ->setLanguage($kw['hl'] ?? 'fr')
            ->setCacheFolder($this->arg('--cache', null))
            ->setNbrPage($this->arg('--page', 1))
            ->setMobile(true)
        ;

        if (10 != $this->args->getParameterOption('--num')) {
            $Google->setParameter('num', $this->arg('--num', 100));
        }

        if (false !== $this->args->getParameterOption('--proxy')) {
            $Google->setProxy($this->args->getParameterOption('--proxy'));
        }

        $result = $Google->extractResults();
        $this->prevError = $Google->getError();

        return $result;
    }

    protected function arg($name, $default)
    {
        return false !== $this->args->getParameterOption($name) ? $this->args->getParameterOption($name) : $default;
    }

    public function exec()
    {
        $this->checkTheSerp();

        // store results and show them

        file_put_contents($this->dir.'/tmp.csv', $this->csvToReturn);

        if (false === $this->failed) {
            exec('libreoffice '.$this->dir.'/tmp.csv');
        }
    }
}
