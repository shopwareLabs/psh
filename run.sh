#!/usr/bin/env bash

set -e

PRES=${1:-""}
docker build -t psh docker/.

docker run -it --rm -v "$(pwd)":/psh -w /psh -u 1000:1000 psh \
/bin/bash -c "./psh ${PRES}" 
