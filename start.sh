#!/bin/bash
set -e  # Exit on any error

echo "🚀 Starting E-commerce Admin..."

# Set production environment
export APP_ENV=production
export APP_DEBUG=false

# Wait for database to be ready
echo "� Waiting for database connection..."
# Export SSL mode and neutralize channel binding that may cause auth issues
export PGSSLMODE=${DB_SSLMODE:-require}
unset PGCHANNELBINDING || true
# Give serverless database time to wake up
sleep 5

# Show all environment variables for debugging
echo "🔍 Environment configuration:"
echo "   APP_ENV: ${APP_ENV:-not_set}"
echo "   DB_CONNECTION: ${DB_CONNECTION:-not_set}"
echo "   DB_HOST: ${DB_HOST:-not_set}"
echo "   DB_PORT: ${DB_PORT:-not_set}"
echo "   DB_DATABASE: ${DB_DATABASE:-not_set}"
echo "   DB_USERNAME: ${DB_USERNAME:-not_set}"

# Check if database variables are set
if [ -z "$DB_HOST" ] || [ "$DB_HOST" = "not_set" ] || [ "$DB_HOST" = "localhost" ]; then
    echo ""
    echo "🔴 =================================="
    echo "🔴 DATABASE NOT CONFIGURED!"
    echo "� =================================="
    echo ""
    echo "📋 NEXT STEPS:"
    echo "   1. Go to Railway Dashboard"
    echo "   2. Select this service"
    echo "   3. Click 'Variables' tab"
    echo "   4. Add these variables:"
    echo ""
    echo "📋 COPY THESE VARIABLES:"
    echo "   DB_CONNECTION=pgsql"
    echo "   DB_HOST=your-database-host.com"
    echo "   DB_PORT=5432"
    echo "   DB_DATABASE=your_database"
    echo "   DB_USERNAME=your_username"
    echo "   DB_PASSWORD=your_password"
    echo ""
    echo "🔄 Railway will auto-redeploy after you save the variables"
    echo "📖 See README.md for detailed instructions"
    echo ""
    echo "⏳ Starting app with temporary config..."
    
    # Set basic defaults to prevent crashes
    export DB_CONNECTION=pgsql
    export DB_HOST=localhost
    export DB_PORT=5432
    export DB_DATABASE=ecommerce
    export DB_USERNAME=postgres
    export DB_PASSWORD=""
fi

# Test database wake-up for serverless databases (Neon, Supabase, etc.)
if [[ "${DB_HOST:-}" == *"-pooler"* ]] || [[ "${DB_HOST:-}" == *"neon"* ]] || [[ "${DB_HOST:-}" == *"supabase"* ]]; then
    echo "⏰ Waking serverless database..."
    php artisan db:monitor --quick 2>/dev/null || true
else
    echo "⏰ Connecting to database..."
fi

# Test database connection with timeout to prevent hanging
echo "🧪 Testing database connection..."
DB_CONNECTED=false

# Use longer timeout for serverless databases that may need to wake up
if timeout 20 php artisan migrate:status; then
    echo "✅ Database connected successfully"
    DB_CONNECTED=true
else
    echo "❌ Database connection failed or timed out"
    echo ""
    echo "💡 Common solutions:"
    echo "   • Add DB_SSLMODE=require for SSL connections"
    echo "   • Check if database server is active"
    echo "   • Verify all DB_* variables are correct"
    echo ""
    echo "🔬 Detailed diagnostics:"
    php artisan db:test-connection --detailed 2>/dev/null || echo "Diagnostics unavailable"
    echo ""
    echo "🧭 Running migrate:status (verbose) for more details..."
    timeout 30 php artisan migrate:status -vvv || echo "migrate:status failed"
    echo ""
    echo "🧭 Attempting migrations (verbose, may still fail)..."
    timeout 60 php artisan migrate --force --no-interaction -vvv || echo "Migrations failed"
    echo ""
    echo "🔁 Retesting database connection after diagnostics..."
    if timeout 20 php artisan migrate:status; then
        echo "✅ Database connected successfully (after diagnostics)"
        DB_CONNECTED=true
    fi
    echo "� Application will start without database migrations"
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📄 Creating .env file..."
    cat > .env << EOF
APP_NAME="${APP_NAME:-E-commerce Admin}"
APP_ENV="${APP_ENV:-production}"
APP_KEY=
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost}"

DB_CONNECTION="${DB_CONNECTION:-pgsql}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-ecommerce}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

# PostgreSQL SSL settings for cloud databases
DB_SSLMODE="${DB_SSLMODE:-require}"

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

CLERK_SECRET_KEY="${CLERK_SECRET_KEY:-}"
INFINITEPAY_CLIENT_ID="${INFINITEPAY_CLIENT_ID:-}"
INFINITEPAY_CLIENT_SECRET="${INFINITEPAY_CLIENT_SECRET:-}"
EOF
fi

# Generate app key if needed
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force --no-interaction
fi

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link --force 2>/dev/null || echo "✅ Storage link already exists"

# Enforce manifest usage in production (avoid dev server hot file)
rm -f public/hot 2>/dev/null || true
echo "🧹 Ensuring Vite manifest mode (removed public/hot if existed)"

# Ensure built assets exist; if not, build them
if [ ! -f public/build/manifest.json ]; then
    echo "🎯 Missing built assets - building with Vite..."
    if [ ! -d node_modules ]; then
        npm ci || npm install
    fi
    npm run build || echo "⚠️ Vite build failed at runtime"
fi

# Optionally keep Vite running
if [ "${APP_ENV}" != "production" ] && [ "${VITE_DEV_SERVER}" = "true" ]; then
    echo "🟡 Starting Vite dev server (non-production environment)"
    (npm run dev -- --host 0.0.0.0 --port="${VITE_PORT:-5173}" 2>&1 | sed 's/^/[vite-dev] /') &
elif [ "${VITE_WATCH:-true}" = "true" ]; then
        echo "🟢 Starting Vite build --watch in background"
        (npm run build -- --watch 2>&1 | sed 's/^/[vite-watch] /') &
    fi

# Ensure SQLite file for images exists (persistent across deploys)
IMAGES_SQLITE_PATH="${IMAGES_SQLITE:-$(pwd)/storage/app/images.sqlite}"
if [ ! -f "$IMAGES_SQLITE_PATH" ]; then
    echo "🧱 Creating NEW SQLite database for images at $IMAGES_SQLITE_PATH"
    mkdir -p "$(dirname "$IMAGES_SQLITE_PATH")"
    touch "$IMAGES_SQLITE_PATH"
    echo "✨ SQLite file created - will persist across deploys if using Railway volume"
else
    echo "✅ SQLite database already exists at $IMAGES_SQLITE_PATH"
    # Show file size to confirm it has data
    FILE_SIZE=$(du -h "$IMAGES_SQLITE_PATH" | cut -f1)
    echo "   File size: $FILE_SIZE"
fi
export IMAGES_SQLITE="$IMAGES_SQLITE_PATH"

# Verify SQLite PHP extensions
if ! php -m | grep -qi sqlite; then
    echo "⚠️ PHP SQLite extensions not detected (pdo_sqlite/sqlite3). Image BLOB storage will fail."
    echo "   Ensure PHP builds include pdo_sqlite and sqlite3."
fi

# Run standard migrations (Postgres)
php artisan migrate --force --no-interaction || echo "⚠️ Postgres migrations failed or already applied"

# Run images migrations on sqlite_images connection
if ls database/migrations_images/*.php >/dev/null 2>&1; then
    echo "🖼️ Running image migrations on sqlite_images..."
    # Check if tables already exist
    if php artisan migrate:status --database=sqlite_images 2>/dev/null | grep -q "product_images"; then
        echo "✅ Image tables already exist - skipping migration"
    else
        echo "🆕 Creating image tables..."
        for file in database/migrations_images/*.php; do
            php artisan migrate --force --no-interaction \
              --path=$(echo "$file" | sed 's#^./##') \
              --database=sqlite_images || echo "⚠️ Failed to run image migration: $file"
        done
    fi
else
    echo "ℹ️ No image migrations found"
fi

# Run database migrations (only if database is connected)
if [ "$DB_CONNECTED" = true ]; then
    echo "🗄️ Running database migrations..."
    php artisan migrate --force --no-interaction || {
        echo "❌ Database migration failed"
        echo "⚠️  Continuing without migrations - check database config"
    }
else
    echo "⚠️  Skipping migrations - database not accessible"
    echo "💡 Fix database connection and redeploy"
fi

# Clear and cache for production
# Optimize for production
echo "⚡ Optimizing application..."
php artisan config:cache --no-interaction

# Route cache sometimes causes segfault in container - skip for safety
echo "📝 Skipping route cache due to segfault risk"

php artisan view:cache --no-interaction

# Set proper permissions
echo "🔒 Setting permissions..."
chmod -R 755 storage bootstrap/cache 2>/dev/null || echo "Permission setup completed"

# Final status check
if [ "$DB_CONNECTED" = true ]; then
    echo ""
    echo "✅ E-commerce Admin ready with database!"
else
    echo ""
    echo "⚠️  E-commerce Admin started WITHOUT database"
    echo "🔧 Add DB_SSLMODE=require to Railway variables and redeploy"
    echo "📖 Check the database connection errors above"
fi

echo ""
echo "🌐 Starting server on port ${PORT:-8000}..."
echo "🔗 Application will be available at your Railway domain"
echo ""

# Start the Laravel server
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}