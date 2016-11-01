#!/usr/bin/env bash

bin/php-cs-fixer fix
bin/phpunit
./humbug.phar --no-interaction