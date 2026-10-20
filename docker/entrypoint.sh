#!/bin/bash
set -e

cd /var/www/html

mkdir -p fuel/app/cache fuel/app/logs fuel/app/tmp public/uploads/blogs

# Windows bind mounts ignore chmod. Linux hosts still need these writable.
chmod -R a+rwX fuel/app/cache fuel/app/logs fuel/app/tmp public/uploads || true

echo "Waiting for MySQL at mysql-local..."
until mysqladmin --ssl --ssl-verify-server-cert=OFF ping -h mysql-local -uuser -puser --silent; do
    sleep 2
done

# development/migrations.php already lists these migrations as applied,
# so a brand-new database would skip them. Create the tables when missing.
if ! mysql --ssl --ssl-verify-server-cert=OFF -h mysql-local -uuser -puser -N -e "SHOW TABLES LIKE 'users'" todo_list | grep -q users; then
    echo "Creating application tables..."
    mysql --ssl --ssl-verify-server-cert=OFF -h mysql-local -uuser -puser todo_list < docker/mysql/schema.sql
fi

if [ -f oil ]; then
    php oil refine migrate
fi

exec "$@"
