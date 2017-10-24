#!/usr/bin/env bash
# DESCRIPTION: Execute unit tests

bin/php-cs-fixer fix
bin/phpunit --debug --verbose --coverage-clover=./build/coverage.xml
./humbug.phar --no-interaction
