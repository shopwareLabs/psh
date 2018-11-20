#!/usr/bin/env bash
# DESCRIPTION: Builds phar packages

php box.phar build
mv psh.phar build/psh.phar
chmod +x build/psh.phar
