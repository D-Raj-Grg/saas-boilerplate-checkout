#!/usr/bin/env bash

SRC_DIR="$PWD"

composer install

# Build billing portal
cd "$SRC_DIR/web/app/mu-plugins/bsf-saas-billing"
composer install --ignore-platform-reqs && composer dump-autoload -o
