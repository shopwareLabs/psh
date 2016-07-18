#!/usr/bin/env bash

bin/php-cs-fixer fix
bin/phpunit --debug --verbose
./humbug.phar --no-interaction