#!/usr/bin/env bash
# DESCRIPTION: Execute unit tests

bin/phpunit --debug --verbose --coverage-clover=./build/coverage.xml --coverage-html=./build/html-coverage
