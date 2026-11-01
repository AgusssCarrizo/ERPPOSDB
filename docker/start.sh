#!/bin/sh
# Arranque del contenedor en Render: con las variables de entorno ya cargadas,
# cachea la configuración, aplica las migraciones pendientes en Neon y levanta Apache.
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force

# Lo de arriba corre como root; Apache (www-data) tiene que poder escribir caché, logs y vistas.
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
