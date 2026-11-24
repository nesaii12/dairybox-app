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

# Start embedded MariaDB automatically when DB_HOST points to localhost / 127.0.0.1
DB_CONN="${DB_CONNECTION:-mysql}"
DB_H="${DB_HOST:-127.0.0.1}"
if [ "${DB_CONN}" = "mysql" ] && { [ "${DB_H}" = "127.0.0.1" ] || [ "${DB_H}" = "localhost" ]; }; then
    echo ">> Starting embedded MariaDB server on 127.0.0.1:3306..."
    mkdir -p /run/mysqld /var/lib/mysql
    chown -R mysql:mysql /run/mysqld /var/lib/mysql

    if [ ! -d /var/lib/mysql/mysql ]; then
        echo ">> Initializing MariaDB data directory..."
        mariadb-install-db --user=mysql --datadir=/var/lib/mysql --skip-test-db >/dev/null 2>&1 || \
            mysql_install_db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1
    fi

    mariadbd --user=mysql --datadir=/var/lib/mysql --socket=/run/mysqld/mysqld.sock --pid-file=/run/mysqld/mysqld.pid >/tmp/mariadb.log 2>&1 &

    echo ">> Waiting for MariaDB to accept connections..."
    READY=0
    for i in $(seq 1 30); do
        if mariadb-admin --socket=/run/mysqld/mysqld.sock ping --silent >/dev/null 2>&1; then
            READY=1
            break
        fi
        sleep 1
    done

    if [ "${READY}" != "1" ]; then
        echo "!! MariaDB failed to start. Log output:"
        tail -n 30 /tmp/mariadb.log || true
        exit 1
    fi

    DB_NAME="${DB_DATABASE:-dairybox}"
    DB_USER="${DB_USERNAME:-root}"
    DB_PASS="${DB_PASSWORD:-}"

    echo ">> Configuring embedded MariaDB database '${DB_NAME}'..."
    SQL_INIT="CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    if [ "${DB_USER}" != "root" ]; then
        SQL_INIT="${SQL_INIT}
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
    fi
    SQL_INIT="${SQL_INIT}
ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('${DB_PASS}');
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
ALTER USER 'root'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
FLUSH PRIVILEGES;"

    if ! printf '%s\n' "${SQL_INIT}" | mariadb --socket=/run/mysqld/mysqld.sock -u root 2>/dev/null; then
        printf '%s\n' "${SQL_INIT}" | mariadb --socket=/run/mysqld/mysqld.sock -u root -p"${DB_PASS}"
    fi
    echo ">> Embedded MariaDB is ready!"
fi

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
