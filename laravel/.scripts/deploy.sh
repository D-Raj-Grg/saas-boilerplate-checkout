#!/bin/bash
set -e

composer install --optimize-autoloader

php artisan optimize:clear

php artisan migrate --force

php artisan optimize

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

php artisan horizon:terminate

# Reload Octane workers with new code (zero downtime)
php artisan octane:reload

echo "Deployment DONE!"