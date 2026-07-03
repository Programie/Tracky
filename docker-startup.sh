#! /bin/bash

set -e

echo "Waiting for database..."
until php /app/bin/console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; do
    sleep 1
done

echo "Database is ready."

echo "Running migrations..."
php /app/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
