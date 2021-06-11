#!/usr/bin/env bash
# DESCRIPTION: Execute full ci suite

bin/psalm
bin/php-cs-fixer fix
INCLUDE: unit.sh

bin/infection --min-msi=80
