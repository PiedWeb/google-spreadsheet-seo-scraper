<p align="center"><a href="https://dev.piedweb.com">
<img src="https://raw.githubusercontent.com/PiedWeb/piedweb-devoluix-theme/master/src/img/logo_title.png" width="200" height="200" alt="Open Source Package" />
</a></p>

# Google Spreadsheet Seo Scraper

[![Latest Version](https://img.shields.io/github/tag/PiedWeb/GoogleSpreadsheetSeoScraper.svg?style=flat&label=release)](https://github.com/PiedWeb/GoogleSpreadsheetSeoScraper/tags)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)
[![Build Status](https://img.shields.io/travis/PiedWeb/GoogleSpreadsheetSeoScraper/master.svg?style=flat)](https://travis-ci.org/PiedWeb/GoogleSpreadsheetSeoScraper)
[![Quality Score](https://img.shields.io/scrutinizer/g/PiedWeb/GoogleSpreadsheetSeoScraper.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/GoogleSpreadsheetSeoScraper)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/PiedWeb/GoogleSpreadsheetSeoScraper.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/GoogleSpreadsheetSeoScraper/code-structure)
[![Total Downloads](https://img.shields.io/packagist/dt/piedweb/google-spreadsheet-seo-scraper.svg?style=flat)](https://packagist.org/packages/piedweb/google-spreadsheet-seo-scraper)

Open source excel or libreoffice and PHP SEO google position SERP checker to track and follow a few website's keywords positions.

Homepage : https://piedweb.com/serp

## Requirements

You need to know how to open execute a command on a CLI.

You need **PHP**, **CURL**, **composer**, **unoconv** and **libreoffice** on your computer.

No direct support (maybe by a peer), use it as you can or look [my prices](https://piedweb.com/#devis).

## Install

Via [Packagist](https://packagist.org/packages/piedweb/google-spreadsheet-seo-scraper)

``` bash
$ # create the folder where you will install the soft
$ mkdir gs3 && cd gs3
$ # install the lib via composer
$ composer require piedweb/google-spreadsheet-seo-scraper
$ # create a link to the executable
$ ln -s vendor/piedweb/google-spreadsheet-seo-scraper/scrap.php console
```

## Usage

``` bash
$ gs3/console
    --ods path/to/myfile.ods
    --domain host.tld
```

### Facultative args

``` bash
--proxy ip:port:username:pass (without proxy, you can check between 20 and 50 kw)
--cache /my/cache/folder/for/google/result (plain html)
--num   100 (default, correspond to google num arg)
--page  1 (default, number of results's page to crawl')
--sleep 40 (default, time to wayt between to request on google)
--quiet
```

### Examples

```
$ php scrap.php --ods "/home/session/project/piedweb.com/seo/kw.ods" --domain piedweb.com

```

```
$ php scrap.php --ods "/home/session/project/piedweb.com/seo/kw.ods" --domain piedweb.com,piedweb.fr

```

```
$ php scrap.php --ods "/home/session/project/piedweb.com/seo/kw.ods" --domain "you can set it directly in your ods file for each row"

```



☯ Without proxy, I use it for a dozen of keywords.

## About  `kw.ods`

> Seule les colonnes jusqu'à J sont importantes.
> Il est possible d'archiver autant de données que voulues dans les colonnes suivantes à condition de ne pas avoir
> deux colonnes portant le même nom.
> le doc peut être agrémenter de plusieurs feuilles à condition que la feuille par défaut reste la première.

> L'**importance** est un indicateur subjectif entre 1 et 10 couplant le volume du mot clef et la capacité estimée de transformer
> un internaute utilisant ce mot clef.
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

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[![Latest Version](https://img.shields.io/github/tag/PiedWeb/GoogleSpreadsheetSeoScraper.svg?style=flat&label=release)](https://github.com/PiedWeb/GoogleSpreadsheetSeoScraper/tags)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)
[![Build Status](https://img.shields.io/travis/PiedWeb/GoogleSpreadsheetSeoScraper/master.svg?style=flat)](https://travis-ci.org/PiedWeb/GoogleSpreadsheetSeoScraper)
[![Quality Score](https://img.shields.io/scrutinizer/g/PiedWeb/GoogleSpreadsheetSeoScraper.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/GoogleSpreadsheetSeoScraper)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/PiedWeb/GoogleSpreadsheetSeoScraper.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/GoogleSpreadsheetSeoScraper/code-structure)
[![Total Downloads](https://img.shields.io/packagist/dt/piedweb/google-spreadsheet-seo-scraper.svg?style=flat)](https://packagist.org/packages/piedweb/google-spreadsheet-seo-scraper)
