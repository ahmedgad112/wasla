#!/usr/bin/env bash
set -euo pipefail

cd /home/halwuecc/wasla.gaddevelopment.site

/usr/local/bin/php -d memory_limit=-1 /home/halwuecc/bin/composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

rm -f public/hot

/usr/local/bin/php artisan migrate --force
/usr/local/bin/php artisan optimize:clear
/usr/local/bin/php artisan storage:link || true
