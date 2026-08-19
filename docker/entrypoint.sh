#!/bin/sh
# Entrypoint de la imagen glucy-api. Corre como root, prepara storage y cachea
# la config con las variables de entorno reales; php-fpm baja a www-data solo.
set -eu

cd /var/www/html

# Los volumenes de storage/app y storage/logs pueden llegar vacios.
mkdir -p storage/app/medico storage/app/private storage/app/public \
         storage/framework/cache/data storage/framework/sessions storage/framework/views \
         storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ "${APP_KEY:-}" = "" ]; then
    echo "[entrypoint] APP_KEY vacio: genera uno con 'php artisan key:generate --show' y ponlo en api.env" >&2
    exit 1
fi

# Cache de config/rutas/eventos con el env de este contenedor.
# Se ejecuta como www-data para que los archivos cacheados sean suyos.
su-exec www-data php artisan optimize --no-interaction

# JSON de Swagger (/api/documentation) desde los atributos #[OA\...].
su-exec www-data php artisan l5-swagger:generate --no-interaction

# Migraciones idempotentes: DB puede tardar en levantar, reintentar.
i=0
until su-exec www-data php artisan migrate --force --no-interaction; do
    i=$((i+1))
    if [ "$i" -ge 10 ]; then
        echo "[entrypoint] migrate fallo tras 10 intentos" >&2
        exit 1
    fi
    echo "[entrypoint] DB no lista, reintento $i/10..." >&2
    sleep 3
done

# Seeders idempotentes (updateOrCreate): catalogos base + usuario admin.
# Desactivar con RUN_SEEDERS=0.
if [ "${RUN_SEEDERS:-1}" = "1" ]; then
    su-exec www-data php artisan db:seed --force --no-interaction
fi

exec "$@"
