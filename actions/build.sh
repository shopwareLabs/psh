#!/usr/bin/env bash
# DESCRIPTION: Builds phar packages

php bin/box compile --debug
mv psh.phar build/psh.phar
chmod +x build/psh.phar