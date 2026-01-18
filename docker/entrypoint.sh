#!/bin/sh
# =============================================================================
# Docker Entrypoint Script
# =============================================================================

set -e

echo "🚀 Starting E-commerce Admin container..."

# Wait for database to be ready
if [ -n "$DB_HOST" ]; then
    echo "⏳ Waiting for database connection..."
    timeout=30
    counter=0
    
    until php artisan migrate:status > /dev/null 2>&1; do
        counter=$((counter + 1))
        if [ $counter -gt $timeout ]; then
            echo "❌ Database connection timeout after ${timeout}s"
            break
        fi
        echo "   Retrying database connection... ($counter/$timeout)"
        sleep 1
    done
    
    if [ $counter -le $timeout ]; then
        echo "✅ Database connected"
    fi
fi

# Run migrations
if [ "$RUN_MIGRATIONS" = "true" ] || [ "$APP_ENV" = "production" ]; then
    echo "📦 Running database migrations..."
    php artisan migrate --force --no-interaction || echo "⚠️ Migrations failed or already applied"
fi

# Clear and cache config
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link if needed
php artisan storage:link 2>/dev/null || true

# Set permissions
echo "🔒 Setting permissions..."
chown -R app:app storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "✅ E-commerce Admin ready!"
echo ""

# Execute CMD
exec "$@"
