#!/usr/bin/env bash

bin/phpunit --debug --verbose
INCLUDE: file_not_exists.sh
bin/phpunit --debug --verbose
