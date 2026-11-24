#!/bin/sh
set -e

# ==========================================
# Dairybox Production Entrypoint for Render
# ==========================================

# Render assigns a dynamic port via $PORT (defaults to 80 if not set)
PORT_HTTP="${PORT:-80}"
echo ">> Setting Apache port to: ${PORT_HTTP}"

sed -i "s/Listen 80/Listen ${PORT_HTTP}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT_HTTP}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure storage and bootstrap directories exist with proper permissions
echo ">> Ensuring directory permissions..."
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# If SQLite is configured and database file does not exist, create it
if [ "${DB_CONNECTION}" = "sqlite" ] || [ -z "${DB_CONNECTION}" ]; then
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        echo ">> Initializing SQLite database file..."
        touch /var/www/html/database/database.sqlite
        chown www-data:www-data /var/www/html/database/database.sqlite
        chmod 664 /var/www/html/database/database.sqlite
    fi
fi

# Create storage symlink
echo ">> Creating storage symlink..."
php artisan storage:link --force || true

# Cache Laravel configuration, routes, and views if APP_KEY is set
if [ -n "$APP_KEY" ]; then
    echo ">> Caching configuration, routes, and views..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Run database migrations only if explicitly enabled via RUN_MIGRATIONS=true
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo ">> Running database migrations..."
    php artisan migrate --force || true
fi

# Start Apache in the foreground
echo ">> Starting Apache web server on port ${PORT_HTTP}..."
exec apache2-foreground
