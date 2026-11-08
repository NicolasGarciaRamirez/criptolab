#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

log() {
    printf '\n[entrypoint] %s\n' "$*"
}

if [ ! -f .env ]; then
    log "Creando .env desde .env.example"
    cp .env.example .env
fi

ensure_base64_key() {
    local name="$1"
    if grep -q "^${name}=base64:" .env; then
        return
    fi

    local value
    value="base64:$(php -r 'echo base64_encode(random_bytes(32));')"

    if grep -q "^${name}=" .env; then
        sed -i "s|^${name}=.*|${name}=${value}|" .env
    else
        printf '\n%s=%s\n' "${name}" "${value}" >> .env
    fi

    log "Generada ${name} (guárdala: sin ella no se pueden descifrar los datos)"
}

ensure_base64_key CRYPTO_MASTER_KEY
ensure_base64_key CRYPTO_BLIND_INDEX_KEY

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache || true

if [ ! -f vendor/autoload.php ]; then
    log "Instalando dependencias de Composer"
    COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --prefer-dist --no-progress
fi

if grep -q '^APP_KEY=$' .env || ! grep -q '^APP_KEY=' .env; then
    log "Generando APP_KEY"
    php artisan key:generate --force
fi

if [ ! -d node_modules ]; then
    log "Instalando dependencias de Node"
    if [ -f package-lock.json ]; then
        npm ci --no-audit --no-fund
    else
        npm install --no-audit --no-fund
    fi
fi

if [ ! -f public/build/manifest.json ]; then
    log "Compilando assets con Vite"
    npm run build
fi

log "Limpiando cachés de Laravel"
php artisan optimize:clear >/dev/null 2>&1 || true

log "Ejecutando migraciones"
php artisan migrate --force

if [ "${AUTO_SEED:-true}" = "true" ]; then
    log "Sembrando datos de demostración (idempotente)"
    php artisan db:seed --force
fi

log "Aplicación lista"
exec "$@"
