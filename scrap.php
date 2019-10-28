#!/usr/bin/env php
<?php

include __DIR__.'/vendor/autoload.php';

use PiedWeb\GoogleSpreadsheetSeoScraper\GoogleSpreadsheetSeoScraper;

try {
    $GoogleSpreadsheetSeoScraper = new GoogleSpreadsheetSeoScraper($argv, __DIR__);
    $GoogleSpreadsheetSeoScraper->exec();
} catch (Exception $e) {
    echo chr(10).$e->getMessage().chr(10).chr(10);
}
