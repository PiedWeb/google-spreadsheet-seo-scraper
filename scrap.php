#!/usr/bin/env php
<?php

// Import external lib
// ---------------------

include __DIR__.'/vendor/autoload.php';

use PiedWeb\GoogleSpreadsheetSeoScraper\GoogleSpreadsheetSeoScraper;

$GoogleSpreadsheetSeoScraper = new GoogleSpreadsheetSeoScraper($argv, __DIR__);
$GoogleSpreadsheetSeoScraper->exec();

