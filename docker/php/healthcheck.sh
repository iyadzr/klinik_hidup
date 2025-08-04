#!/bin/bash
# Health check script for production
if [ -f "bin/console" ]; then
    php bin/console doctrine:query:sql "SELECT 1" --env=${APP_ENV:-prod} > /dev/null 2>&1
    exit $?
else
    exit 1
fi