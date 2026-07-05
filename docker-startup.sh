#! /bin/bash

set -e

echo "Waiting for database..."
until php /app/bin/console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; do
    sleep 1
done

echo "Database is ready."

if [[ ${APP_RUN_MIGRATIONS:-true} == "true" ]]; then
    echo "Running migrations..."
    php /app/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
else
    echo "Skip running migrations due to APP_RUN_MIGRATIONS = ${APP_RUN_MIGRATIONS}"
    echo "If needed, manually run the migrations using this command:"
    echo "  php /app/bin/console doctrine:migrations:migrate --allow-no-migration"
fi

chown -R www-data: /app/var
