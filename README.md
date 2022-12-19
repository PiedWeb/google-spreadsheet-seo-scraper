<p align="center"><a href="https://dev.piedweb.com">
<img src="https://raw.githubusercontent.com/PiedWeb/piedweb-devoluix-theme/master/src/img/logo_title.png" width="200" height="200" alt="Open Source Package" />
</a></p>

# Google Spreadsheet Seo Scraper

[![Latest Version](https://img.shields.io/github/tag/PiedWeb/PiedWeb.svg?style=flat&label=release)](https://github.com/PiedWeb/PiedWeb/tags)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/PiedWeb/PiedWeb/run-tests.yml?branch=main)](https://github.com/PiedWeb/PiedWeb/actions)
[![Quality Score](https://img.shields.io/scrutinizer/g/PiedWeb/PiedWeb.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/PiedWeb)
[![Code Coverage](https://codecov.io/gh/PiedWeb/PiedWeb/branch/main/graph/badge.svg)](https://codecov.io/gh/PiedWeb/PiedWeb/branch/main)
[![Type Coverage](https://shepherd.dev/github/PiedWeb/PiedWeb/coverage.svg)](https://shepherd.dev/github/PiedWeb/PiedWeb)
[![Total Downloads](https://img.shields.io/packagist/dt/piedweb/google-spreadsheet-seo-scraper.svg?style=flat)](https://packagist.org/packages/piedweb/google-spreadsheet-seo-scraper)

Open source excel/libreoffice and PHP SEO google position SERP checker to track and follow a few website's keywords positions.

Homepage : https://piedweb.com/seo/serp

## Requirements

**PHP**, **CURL**, **composer**, **unoconv** and **libreoffice**

## Install

Via [Packagist](https://packagist.org/packages/piedweb/google-spreadsheet-seo-scraper)

```bash
$ # create the folder where you will install the soft
$ mkdir gs3 && cd gs3
$ # install the lib via composer
$ composer require piedweb/google-spreadsheet-seo-scraper
$ # create a link to the executable
$ ln -s vendor/piedweb/google-spreadsheet-seo-scraper/scrap.php console && chmod +x console
```

## Usage

```bash
$ gs3/console
    --ods path/to/myfile.ods
    --domain host.tld
```

### Facultative args

```bash
--proxy ip:port:username:pass (without proxy, you can check between 20 and 50 kw)
--cache /my/cache/folder/for/google/result (plain html)
--num-100 per default, the script check only the first result page.
          Using it permit to check the 100st results if the domain was not found
--sleep 60 (default, time to wait in seconds between to request on google)
--quiet
```

### Examples

```
$ php scrap.php --ods "./kw.ods" --domain piedweb.com

```

```
$ php scrap.php --ods "./kw.ods" --domain piedweb.com,piedweb.fr

```

```
$ php scrap.php --ods "./kw.ods" --domain "you can set it directly in your ods file for each row"

```

## About `kw.ods`

> Seule les colonnes jusqu'à J sont importantes.
> Il est possible d'archiver autant de données que voulues dans les colonnes suivantes à condition de ne pas avoir
> deux colonnes portant le même nom.
> le doc peut être agrémenter de plusieurs feuilles à condition que la feuille par défaut reste la première.

> [Robin Delattre](https://www.robin-d.fr/)

Debugging `tendance` formula

```
=IF(J6="";"";                   // Si prev_pos est vide alors rien
    IF (J6="FAILED";"";            // Si prev_pos a échoué, alors rien
        IF(H6="FAILED";"";              // Si pos a échoué alors rien
             IF(H6="";"";                   // Si pos est vide alors rien
                IF(H6=-1;"x";                   // si kw n'est pas position, alors x
                    IF (H6=J6;"=";
                        IF(H6<J6;"+";
                            IF(J6=-1;"+";"-")
                        )
                    )
                )
            )
        )
    )
)
```

Legend for `tendance`

```
+ : le site grimpe vers la meilleur position
- : le site descend
x : le site n'est pas possitionnent
  : aucun résultat à analyser
```

## Credits

- [PiedWeb](https://piedweb.com)
- [All Contributors](https://github.com/PiedWeb/GoogleSpreadsheetSeoScraper/graphs/contributors)
