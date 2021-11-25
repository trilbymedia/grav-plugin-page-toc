# v2.0.0
## 11/24/2021

1. [](#new)
   * Added new `components/page-toc.html.twig` that can be extended and the HTML output modified
   * Updated core TOC library to latest `3.0.2` version
   * Requires PHP `7.3.6`
   * Requires Grav `1.7+`
   * Added Shortcode-like in-page syntax support. e.g. `[toc]`

# v1.1.2
## 06/01/2021

1. [](#new)
    * Added page-toc blueprints under "Advanced" tab for admin
1. [](#improved)
    * Updated to latest `knplabs/knp-menu` library
1. [](#bugfix)
    * Added `|raw` filter to twig output in README.md

# v1.1.1
## 12/02/2020

1. [](#improved)
    * Updated to latest `masterminds/html5` and `knplabs/knp-menu` libraries

# v1.1.0
## 04/01/2019

1. [](#improved)
    * Updated to latest `caseyamcl/toc` library
1. [](#bugfix)
    * Fixes relative levels [#6](https://github.com/trilbymedia/grav-plugin-page-toc/pull/9)
    * Fixes incorrect reference to `end` when it should be `depth` [#7](https://github.com/trilbymedia/grav-plugin-page-toc/pull/7)

# v1.0.1
## 03/19/2017

1. [](#improved)
    * Fixed issue with `end` not being valid, should be `depth`. Updated README

# v1.0.0
## 08/01/2017

1. [](#new)
    * ChangeLog started...
