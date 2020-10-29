#!/usr/bin/env bash
# DESCRIPTION: Builds phar packages

I: rm build/*.phar
php bin/box compile --debug
mv psh.phar build/psh.phar
chmod +x build/psh.phar
rm -R .box_dump