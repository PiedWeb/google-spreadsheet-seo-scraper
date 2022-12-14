#!/usr/bin/env php
<?php

$currentDir = __DIR__;
$expectedEnd = '/vendor/piedweb/google-spreadsheet-seo-scraper';
$dir = (substr($currentDir, -strlen($expectedEnd)) == $expectedEnd) ? substr($currentDir, 0, -strlen($expectedEnd)) : $currentDir.'/../..';

include $dir.'/vendor/autoload.php';

use PiedWeb\GoogleSpreadsheetSeoScraper\GoogleSpreadsheetSeoScraper;

executeScrap();

$message = 'Terminé';
exec("export DISPLAY=:0; notify-send 'title' '$message' ");

function executeScrap($i = 1)
{
    global $argv;

    try {
        $GoogleSpreadsheetSeoScraper = new GoogleSpreadsheetSeoScraper($argv);
        $GoogleSpreadsheetSeoScraper->exec();
    } catch (Exception $e) {
        echo chr(10).$e->getMessage().chr(10).chr(10);

        if (file_exists(__DIR__.'/../perso/rebooBox.php')) {
            echo 'Rebooting box and continue ('.$i.')...'.chr(10).chr(10);
            ++$i;
            exec('php "'.__DIR__.'/../perso/rebooBox.php"');
            sleep(60 * 5);
            if (! in_array('--retry', $argv)) {
                $argv[] = '--retry';
                $argv[] = 'last';
            }
            executeScrap($i);
        }
    }
}
