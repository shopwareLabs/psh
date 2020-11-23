#!/usr/bin/env bash

set -e

PHPV="74"
PRES=${1:-""}
docker build -t psh${PHPV} docker/${PHPV}/.

docker run \
  -it \
  --rm \
  --volume source=${HOME}/.composer,target="./composer" \
  -v "$(pwd)":/psh \
  -w /psh \
  -u 1000:1000 psh${PHPV} \
  /bin/bash -c "./psh ${PRES}"
