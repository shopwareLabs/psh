#!/bin/bash

ls -ahl .travis/

# Setup SSH agent
chmod 600 .travis/github_deploy_key
eval "$(ssh-agent -s)"
ssh-add .travis/github_deploy_key

# Setup git defaults
git config --global user.email "contact@shopware.com"
git config --global user.name "shopwareBot"

# Add SSH-based remote
git remote add deploy git@github.com:shopwareLabs/psh.git
git fetch deploy

## Checkout gh-pages and add PHAR file and version
git checkout -b gh-pages deploy/gh-pages
mv build/psh.phar psh.phar
sha1sum psh.phar > psh.phar.version

mv build/psh56.phar psh56.phar
sha1sum psh56.phar > psh56.phar.version

git add psh.phar psh.phar.version psh56.phar psh56.phar.version

git status


# Commit and push
git commit -m 'Rebuilt phar'
git log
git push deploy gh-pages:gh-pages