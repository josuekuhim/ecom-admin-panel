# =============================================================================
# Makefile - E-commerce Admin
# Convenient commands for Docker and development
# =============================================================================

.PHONY: help install up down restart logs shell test lint build deploy clean

# Default target
help:
	@echo ""
	@echo "🛒 E-commerce Admin - Available Commands"
	@echo "========================================="
	@echo ""
	@echo "Development:"
	@echo "  make install    - Install dependencies and setup project"
	@echo "  make up         - Start development environment"
	@echo "  make down       - Stop development environment"
	@echo "  make restart    - Restart all containers"
	@echo "  make logs       - View container logs"
	@echo "  make shell      - Open shell in app container"
	@echo ""
	@echo "Testing & Quality:"
	@echo "  make test       - Run tests"
	@echo "  make lint       - Run linting (Pint + PHPStan)"
	@echo "  make coverage   - Run tests with coverage"
	@echo ""
	@echo "Production:"
	@echo "  make build      - Build production Docker image"
	@echo "  make deploy     - Deploy to production"
	@echo "  make prod-up    - Start production environment"
	@echo ""
	@echo "Maintenance:"
	@echo "  make clean      - Remove all containers and volumes"
	@echo "  make fresh      - Fresh install (clean + install + up)"
	@echo ""

# ---------------------------------------------------------------------------
# Development
# ---------------------------------------------------------------------------

install:
	@echo "📦 Installing dependencies..."
	docker compose run --rm app composer install
	docker compose run --rm vite npm install
	cp -n .env.example .env || true
	docker compose run --rm app php artisan key:generate
	@echo "✅ Installation complete!"

up:
	@echo "🚀 Starting development environment..."
	docker compose up -d
	@echo ""
	@echo "✅ Environment ready!"
	@echo "   App:   http://localhost:8000"
	@echo "   Vite:  http://localhost:5173"
	@echo ""

down:
	@echo "🛑 Stopping environment..."
	docker compose down

restart:
	@echo "🔄 Restarting containers..."
	docker compose restart

logs:
	docker compose logs -f

shell:
	docker compose exec app sh

shell-db:
	docker compose exec db psql -U ecommerce -d ecommerce

# ---------------------------------------------------------------------------
# Testing & Quality
# ---------------------------------------------------------------------------

test:
	@echo "🧪 Running tests..."
	docker compose exec app php artisan test

test-parallel:
	@echo "🧪 Running tests in parallel..."
	docker compose exec app php artisan test --parallel

coverage:
	@echo "📊 Running tests with coverage..."
	docker compose exec app php artisan test --coverage

lint:
	@echo "🔍 Running Pint..."
	docker compose exec app vendor/bin/pint
	@echo "🔍 Running PHPStan..."
	docker compose exec app vendor/bin/phpstan analyse

lint-fix:
	@echo "🔧 Fixing code style..."
	docker compose exec app vendor/bin/pint

# ---------------------------------------------------------------------------
# Database
# ---------------------------------------------------------------------------

migrate:
	docker compose exec app php artisan migrate

migrate-fresh:
	docker compose exec app php artisan migrate:fresh --seed

seed:
	docker compose exec app php artisan db:seed

# ---------------------------------------------------------------------------
# Production
# ---------------------------------------------------------------------------

build:
	@echo "🏗️ Building production image..."
	docker build -t ecommerce-admin:latest -f Dockerfile --target production .

prod-up:
	@echo "🚀 Starting production environment..."
	docker compose -f docker-compose.prod.yml up -d

prod-down:
	docker compose -f docker-compose.prod.yml down

deploy:
	@echo "🚀 Deploying to production..."
	@echo "⚠️  Configure your deployment target in this command"
	# docker push your-registry/ecommerce-admin:latest
	# kubectl apply -f k8s/
	# railway up
	# etc.

# ---------------------------------------------------------------------------
# Maintenance
# ---------------------------------------------------------------------------

clean:
	@echo "🧹 Cleaning up..."
	docker compose down -v --remove-orphans
	docker compose -f docker-compose.prod.yml down -v --remove-orphans 2>/dev/null || true
	rm -rf node_modules vendor public/build
	@echo "✅ Cleanup complete!"

fresh: clean install up
	@echo "✅ Fresh environment ready!"

# Cache
cache-clear:
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

cache:
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache
