#!/usr/bin/env bash
# DESCRIPTION: Execute unit tests

I: bin/php-cs-fixer.phar fix
I: bin/phpstan.phar analyse --level max src/
bin/phpunit --debug --verbose --coverage-clover=./build/coverage.xml --coverage-html=./build/html-coverage
./infection.phar