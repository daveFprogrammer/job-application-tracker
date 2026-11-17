#!/bin/sh
set -e

# Render's free tier has no pre-deploy hook: run migrations at boot.
# (No --isolated: its lock lives in the database cache table, which does not
# exist yet on the very first deploy, and the free plan runs one instance.)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

php artisan optimize

exec "$@"
