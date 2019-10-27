<?php

namespace PiedWeb\GoogleSpreadsheetSeoScraper;

use rOpenDev\Google\SearchViaCurl;
use Symfony\Component\Console\Input\ArgvInput;
use League\Csv\Reader;

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
     * @var array
     */
    protected $records;

    /**
     * contain csv result data
     * @var string
     */
    protected $csvToReturn = '';

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
            exit(chr(10).'At least 1 parameter is missing : --ods or --domain'.chr(10).chr(10));
        }
        if (!file_exists($this->args->getParameterOption('--ods'))) {
            exit(chr(10).'--ods path is not working'.chr(10).chr(10));
        }

    }


    protected function extractData()
    {
        exec('unoconv -o "'.$this->dir.'/tmp.csv" -f csv '.$this->args->getParameterOption('--ods').'');

        $csv = Reader::createFromPath($this->dir .'/tmp.csv', 'r');
        $csv->setHeaderOffset(0);

        $this->records = $csv->getRecords();
    }

    protected function checkTheSerp()
    {
        foreach ($this->records as $r) {

            // MAYBE WE ever checked the pos
            if ($r['pos'] !== '' && $r['pos'] !== 'FAILED') {
                $this->csvToReturn .= $r['pos'].',"'.$r['url'].'"'.chr(10);
            } else {

                // DEBUG
                echo $r['kw']. ' ('.$r['tld'].';'.$r['hl'].')'.chr(10);

                $Google = new SearchViaCurl($r['kw']);
                $Google
                     ->setTld($r['tld'])
                     ->setLanguage($r['hl'])
                     ->setCacheFolder($this->arg('--cache', false))
                     //->setCacheFolder(__DIR__ .'/cache')
                     ->setNbrPage($this->arg('--page', 1))
                ;

                if ($this->args->getParameterOption('--num') != '10') {
                     $Google->setParameter('num', $this->arg('--num', 100));
                }

                if ($this->args->getParameterOption('--proxy') !== false) {
                    $Google->setProxy($this->args->getParameterOption('--proxy'));
                }
                $results = $Google->extractResults();

                if ($results) {
                    foreach ($results as $k => $r) {
                        if (parse_url($r['link'], PHP_URL_HOST) == $this->args->getParameterOption('--domain')) {
                            $result = ($k+1).','.$r['link'].chr(10); break;
                        }
                    }

                    $this->csvToReturn .= isset($result) ? $result : '"-1",""'.chr(10);
                    unset($result);

                } else {
                    // DEBUG
                    echo $Google->getError().chr(10);
                    $this->csvToReturn .= '"FAILED",""'.chr(10);
                }


                echo '------------'.chr(10);
                sleep($this->arg('--sleep', 40));
            }
        }
    }

    protected function arg($name, $default) {
        return $this->args->getParameterOption($name) !== false ? $this->args->getParameterOption($name) : $default;
    }

    public function exec()
    {
        $this->checkTheSerp();

        // store results and show them

        file_put_contents($this->dir.'/tmp.csv', $this->csvToReturn);

        exec('libreoffice '.$this->dir.'/tmp.csv');

        //unlink($this->dir.'/tmp.csv');
    }
}
