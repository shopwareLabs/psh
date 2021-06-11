#!/usr/bin/env bash
# DESCRIPTION: Execute unit tests

bin/phpunit --coverage-clover=./build/coverage.xml --coverage-html=./build/html-coverage
