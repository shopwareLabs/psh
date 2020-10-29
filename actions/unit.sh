#!/usr/bin/env bash
# DESCRIPTION: Execute unit tests

phpdbg -qrr bin/phpunit --coverage-clover=./build/coverage.xml --coverage-html=./build/html-coverage
