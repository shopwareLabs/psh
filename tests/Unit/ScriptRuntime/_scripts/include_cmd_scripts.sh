#!/usr/bin/env bash

bin/phpunit --debug --verbose
INCLUDE: ./tests/Unit/ScriptRuntime/_scripts/.cmd1.sh
INCLUDE: .cmd2.sh
bin/phpunit --debug --verbose
