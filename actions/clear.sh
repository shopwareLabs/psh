#!/usr/bin/env bash
# DESCRIPTION: Remove build artifacts
# <PSH_EXECUTE_THROUGH_CMD>
set -euo pipefail

rm -Rf build/*
rm -Rf vendor-bin/*/vendor
rm -Rf bin
rm -Rf vendor
