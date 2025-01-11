# SimpleSAMLphp logpeek module

![Build Status](https://github.com/simplesamlphp/simplesamlphp-module-logpeek/actions/workflows/php.yml/badge.svg)
[![Coverage Status](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-logpeek/branch/master/graph/badge.svg)](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-logpeek)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-logpeek/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-logpeek/?branch=master)
[![Type Coverage](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-logpeek/coverage.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-logpeek)
[![Psalm Level](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-logpeek/level.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-logpeek)

This module provides a web API that you can use to search for all to lines in the logs corresponding to a specific
session identifier.

## Install

Install with composer

```bash
vendor/bin/composer require simplesamlphp/simplesamlphp-module-logpeek
```

## Configuration

Next thing you need to do is to enable the module:

in `config.php`, search for the `module.enable` key and set `logpeek` to true:

```php
    'module.enable' => [ 'logpeek' => true, … ],
```


Change log
----------

### 1.2 (2025-01-11)

- SimpleSAMLphp 2.3 compatibility
- Back button and `backurl` config option added;
- translations (hu);

### 1.1 (2020-06-22)

- Added authorization config parameters (see config-templates/module-logpeek.php)
- Preserve last search tag
- Detailed error display
