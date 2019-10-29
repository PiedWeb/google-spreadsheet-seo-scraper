<?php

namespace PiedWeb\GoogleSpreadsheetSeoScraper;

use rOpenDev\Google\SearchViaCurl;
use Symfony\Component\Console\Input\ArgvInput;
use League\Csv\Reader;
use Exception;

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

    /**
     * @var string
     */
    protected $prevError;

    public function __construct($argv, string $dir)
    {
        $this->dir = $dir;

        $this->loadArgv($argv);
        $this->extractData();
    }

    protected function loadArgv($argv)
    {
        $this->args = new ArgvInput($argv);

        if (!$this->args->getParameterOption('--ods') || !$this->args->getParameterOption('--domain')) {
            throw new Exception('At least 1 parameter is missing : --ods or --domain');
        }
        if (!file_exists($this->args->getParameterOption('--ods'))) {
            throw new Exception('--ods path is not working'.chr(10).chr(10));
        }

        if ($this->args->getParameterOption('--quiet')) {
            $this->quiet = true;
        }

        $this->domain = explode(',', $this->args->getParameterOption('--domain'));
    }

    protected function extractData()
    {
        $tmpCsvFile = $this->dir.'/tmp.csv';
        if (file_exists($this->dir.'/tmp.csv')) {
            unlink($this->dir.'/tmp.csv');
        }

        $comand = 'unoconv -o "'.$tmpCsvFile.'" -f csv "'.$this->args->getParameterOption('--ods').'"';

        exec($comand);

        $csv = Reader::createFromPath($this->dir.'/tmp.csv', 'r');
        $csv->setHeaderOffset(0);

        $this->kws = $csv->getRecords();
    }

    protected function checkTheSerp()
    {
        $kwsNbr = iterator_count($this->kws);

        foreach ($this->kws as $i => $kw) {
            // MAYBE WE ever checked the pos
            if (isset($kw['pos']) && '' !== $kw['pos'] && 'FAILED' !== $kw['pos']) {
                $this->csvToReturn .= $kw['pos'].',"'.$kw['url'].'"'.chr(10);
            } else {
                $this->messageForCli($kw['kw'].' ('.$kw['tld'].';'.$kw['hl'].')');

                $results = $this->getGoogleResults($kw);

                if ($results) {
                    $this->parseGoogleResults($results, $kw);
                } else {
                    $this->messageForCli('An error occured during the request to Google...'.chr(10).$this->prevError);
                    $this->csvToReturn .= '"FAILED",""'.chr(10);

                    return; // quit if we get a captcha ?!
                }

                $this->messageForCli('------------');

                if ($i !== $kwsNbr) {
                    sleep($this->arg('--sleep', 60));
                }
            }
        }
    }

    protected function parseGoogleResults($results, $kw)
    {
        foreach ($results as $k => $r) {
            $host = parse_url($r['link'], PHP_URL_HOST);
            if ((isset($kw['domain']) && $kw['domain'] == $host)
                || in_array($host, $this->domain)
            ) {
                $result = ($k + 1).','.$r['link'].chr(10);
                break;
            }
        }

        $this->csvToReturn .= isset($result) ? $result : '"-1",""'.chr(10);
    }

    protected function messageForCli($msg)
    {
        if (!$this->quiet) {
            echo $msg.chr(10);
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
        ;

        if (10 != $this->args->getParameterOption('--num')) {
            $Google->setParameter('num', $this->arg('--num', 100));
        }

        if (false !== $this->args->getParameterOption('--proxy')) {
            $Google->setProxy($this->args->getParameterOption('--proxy'));
        }

        $this->prevError = $Google->getError();

        return $Google->extractResults();
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

        exec('libreoffice '.$this->dir.'/tmp.csv');
    }
}
