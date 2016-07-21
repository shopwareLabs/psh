#!/usr/bin/env bash

rm -R build/php56
mkdir build/php56

cp psh build/php56
cp composer.json build/php56
cp box.json build/php56
cp box.phar build/php56
cp -R vendor build/php56
bin/php-version-transpiler src/ build/php56/src

cd build/php56 && php box.phar build
mv build/php56/psh.phar build/psh56.phar
chmod +x build/psh56.phar

php box.phar build
mv psh.phar build/psh.phar
chmod +x build/psh.phar

cp build/psh.phar ../b2b/
cp build/psh56.phar ../b2b/

rm -R build/php56