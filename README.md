# dhtmlxScheduler with plain PHP Backend

Implementing scheduler backend in plain PHP without extra libraries and frameworks.

## Requirements

- Web server that supports php (apache, nginx, php-fpm, build-in php server, etc)
- MySql
- PHP 5.4+ with **php_pdo** extension installed

How to install **php_pdo**: https://www.php.net/manual/en/pdo.installation.php

## Setup

- clone or download the repository into the root folder of your web server (e.g. `htdocs` for Apache)

```
$ git clone git@github.com:DHTMLX/scheduler-howto-php-plain.git
$ cd ./scheduler-howto-php-plain
```

- import database from **mysql_dump.sql**
- update db connection settings in data/config.php

- open `http://localhost/scheduler-howto-php-plain`

## Links

- Tutorial: coming soon