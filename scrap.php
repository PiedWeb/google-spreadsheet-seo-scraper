#!/usr/bin/env php
<?php

$currentDir = __DIR__;
$expectedEnd = '/vendor/piedweb/google-spreadsheet-seo-scraper';
$dir = (substr($currentDir, -strlen($expectedEnd)) == $expectedEnd) ? substr($currentDir, 0, -strlen($expectedEnd)) : $currentDir.'/../..';

include $dir.'/vendor/autoload.php';

use PiedWeb\GoogleSpreadsheetSeoScraper\GoogleSpreadsheetSeoScraper;

try {
    $GoogleSpreadsheetSeoScraper = new GoogleSpreadsheetSeoScraper($argv);
    $GoogleSpreadsheetSeoScraper->exec();
} catch (Exception $e) {
    echo chr(10).$e->getMessage().chr(10).chr(10);
}
