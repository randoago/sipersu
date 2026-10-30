#!/bin/sh
set -e
cd /var/www/html

[ -f .env ] || cp .env.example .env
# Nilai dari environment compose menimpa .env (APP_KEY dibuat sekali lalu disimpan di volume storage)
if ! grep -q '^APP_KEY=base64:' .env; then
  if [ -f storage/app.key ]; then
    sed -i "s|^APP_KEY=.*|APP_KEY=$(cat storage/app.key)|" .env
  else
    php artisan key:generate --force
    grep '^APP_KEY=' .env | cut -d= -f2- > storage/app.key
  fi
fi

mkdir -p database storage/keys storage/logs storage/framework/cache storage/framework/sessions storage/framework/views
cp -rf /opt/database-dist/. database/    # segarkan migrasi/seeder dari image (volume database menyimpan salinan lama)
BARU=0
[ -s database/sipersu.sqlite ] || { : > database/sipersu.sqlite; BARU=1; }
chown -R www-data:www-data storage database bootstrap/cache

php artisan migrate --force
[ "$BARU" = "1" ] && php artisan db:seed --force
[ -f storage/keys/ed25519.secret ] || php artisan kunci:buat
php artisan kunci:publikasi || true
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage database bootstrap/cache

# Scheduler (antrean email, backup) setiap menit
( while true; do su -s /bin/sh www-data -c "php artisan schedule:run" >> storage/logs/scheduler.log 2>&1; sleep 60; done ) &

exec "$@"
