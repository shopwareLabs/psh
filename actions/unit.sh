#!/usr/bin/env bash
# DESCRIPTION: Execute unit tests

D: bin/php-cs-fixer fix
phpdbg -qrr bin/phpunit --coverage-clover=./build/coverage.xml --coverage-html=./build/html-coverage

WAIT:

phpdbg -qrr bin/infection --min-msi=80
