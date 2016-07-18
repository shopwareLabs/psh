#!/usr/bin/env bash

php box.phar build
chmod +x build/psh.phar
cp build/psh.phar ../b2b/