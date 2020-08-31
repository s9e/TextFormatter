## Overview

s9e\\TextFormatter is a text formatting library that supports BBCode, Markdown, HTML and other markup via plugins. The library is written in PHP, with a JavaScript port also available for client-side preview (see below.)

[![Packagist Version](https://img.shields.io/packagist/v/s9e/text-formatter)](https://packagist.org/packages/s9e/text-formatter)
[![Build Status](https://api.travis-ci.org/s9e/TextFormatter.svg?branch=master)](https://travis-ci.org/s9e/TextFormatter)
[![Coverage Status](https://coveralls.io/repos/github/s9e/TextFormatter/badge.svg?branch=master)](https://coveralls.io/github/s9e/TextFormatter?branch=master)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/s9e/TextFormatter/badges/quality-score.png?s=3942dab3c410fb9ce02001e7446d1083fa91172c)](https://scrutinizer-ci.com/g/s9e/TextFormatter/)
[![Documentation](https://readthedocs.org/projects/s9etextformatter/badge/)](https://s9etextformatter.readthedocs.io/)


## Installation

The best way to install s9e\\TextFormatter is via Composer. See [Installation](https://s9etextformatter.readthedocs.io/Getting_started/Installation/).

```bash
composer require s9e/text-formatter
```


## Examples

If you can only read one example, [read how to use a bundle](https://s9etextformatter.readthedocs.io/Getting_started/Using_predefined_bundles/).

You can run the scripts directly from the [examples directory](https://github.com/s9e/TextFormatter/blob/master/docs/examples) and you will find in [the manual](https://s9etextformatter.readthedocs.io/) a description of each plugin as well as other examples.


## Versioning

Versioning is meant to follow [Semantic Versioning](https://semver.org/). You can [read about API changes in the documentation](https://s9etextformatter.readthedocs.io/Internals/API_changes/).


## Online demo

You can try the JavaScript version in this [BBCodes + other stuff demo](https://s9e.github.io/TextFormatter/demo.html), or this [Markdown + stuff (Fatdown) demo](https://s9e.github.io/TextFormatter/fatdown.html).


## Development tools

 - [phpunit/phpunit](https://phpunit.de/) 9.3.8 runs a full suite of tests before every commit.
 - [code-lts/doctum](https://github.com/code-lts/doctum) 5.1.0 generates the [https://s9e.github.io/TextFormatter/api/](API docs).
