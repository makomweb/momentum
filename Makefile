.PHONY: help build init symfony laravel up hint shell down composer-install maintain composer-update composer-require caddyfile logs open docker-push sa cs test arch qa cc clear cache-clear cache-pool-clear db create-database create-schema reset restart

help:
	@echo ""
	@echo "╔════════════════════════════════════════════════════════════════════════════╗"
	@echo "║                     🚀 MOMENTUM - Make Targets 🚀                          ║"
	@echo "╚════════════════════════════════════════════════════════════════════════════╝"
	@echo ""
	@echo "📦 SETUP & MANAGEMENT"
	@echo "  boot                  ⚙️  Build images, install deps, start stack, init DB"
	@echo "  up                    ⬆️  Start the stack"
	@echo "  down                  ⬇️  Stop the stack"
	@echo "  restart               🔄 Restart app and worker containers"
	@echo ""
	@echo "🔧 DEVELOPMENT"
	@echo "  shell                 🐚 Open bash on app container"
	@echo "  build                 🏗️  Build Docker images"
	@echo "  logs                  📋 View app logs (tail -f)"
	@echo "  open                  🌐 Open the app in a browser"
	@echo "  hint                  💡 Print local app URLs"
	@echo ""
	@echo "📚 COMPOSER"
	@echo "  composer-install      📦 Run composer install"
	@echo "  maintain              🔄 Run composer update -W"
	@echo "  composer-require      ➕ Add a package (use PKG=vendor/package)"
	@echo ""
	@echo "🗄️  DATABASE"
	@echo "  db                    🔧 Create database and schema"
	@echo "  create-database       📊 Create database if not exists"
	@echo "  create-schema         📐 Create tables from entity definitions"
	@echo ""
	@echo "⚡ CACHE"
	@echo "  cc / clear            🗑️  Clear all caches (app + pool)"
	@echo "  cache-clear           🧹 Clear Symfony app cache only"
	@echo "  cache-pool-clear      🧹 Clear cache pools only"
	@echo ""
	@echo "✅ QUALITY ASSURANCE"
	@echo "  qa                    🎯 Run full QA suite (SA + CS + Test + Arch)"
	@echo "  sa                    🔍 Static code analysis (PHPStan level 8)"
	@echo "  cs                    🎨 Fix code style (Symfony rules)"
	@echo "  test                  ✓  Run PHPUnit tests"
	@echo "  arch                  📐 Run architecture tests (Deptrac)"
	@echo ""
	@echo "🚢 DEPLOYMENT"
	@echo "  docker-push           🐳 Build and push multi-arch image (amd64 + arm64)"
	@echo ""


boot: build composer-install up create-database create-schema

build:
	@echo "Building Docker images..."
	docker compose build

up:
	@echo "Starting the stack..."
	docker compose up -d
	@echo "Stack started!"
	@$(MAKE) hint

hint:
	@echo ""
	@echo "✨ Application URLs:"
	@echo "  🌐 App       → http://localhost:8080"
	@echo "  📊 Dashboard → http://localhost:8001 (links to all services)"
	@echo "  🗄️  Database  → http://localhost:8085 (Adminer, user: root, pwd: secret)"
	@echo ""
	@echo "💡 Tip: Use 'make logs' to view logs"
	@echo ""

shell:
	@echo "Open shell on app container..."
	docker compose exec -it app bash

down:
	@echo "Stopping the stack..."
	docker compose down

composer-install:
	@echo "Running composer install..."
	docker compose run --rm composer install

maintain:
	@echo "Running composer update -W..."
	docker compose run --rm composer show --outdated
	docker compose run --rm composer update -W

composer-update: maintain

composer-require:
	@test -n "$(PKG)" || (echo "Error: specify package with PKG=vendor/package"; exit 1)
	@echo "Adding package $(PKG)..."
	docker compose run --rm composer require $(PKG)

db: create-database create-schema

create-database:
	@echo "Create database"
	docker compose exec -it app bin/console doctrine:database:create --if-not-exists

create-schema:
	@echo "Create database schema"
	docker compose exec -it app bin/console doctrine:schema:update --force

logs:
	docker compose logs -f app

sa:
	@echo "Run static code analysis..."
	docker compose exec -it app vendor/bin/phpstan analyse --memory-limit=1G

cs:
	@echo "Fix code style..."
	docker compose exec -it app vendor/bin/php-cs-fixer fix

test:
	@echo "Run tests..."
	docker compose exec -it app bin/phpunit

arch:
	@echo "Run architecture tests..."
	docker compose exec -it app vendor/bin/deptrac  analyse --report-uncovered

qa: sa cs test arch

cc: clear
clear: cache-clear cache-pool-clear
	@echo "All caches cleared (app + pool)"

cache-clear:
	@echo "Clearing Symfony application cache..."
	docker compose exec -it app bin/console cache:clear

cache-pool-clear:
	@echo "Clearing cache pools..."
	docker compose exec -it app bin/console cache:pool:clear --all

reset: restart
restart:
	@echo "Restarting app, worker containers..."
	docker compose restart app
	docker compose restart worker

open:
	@if command -v xdg-open > /dev/null 2>&1; then \
		xdg-open http://localhost:8001 2>/dev/null & \
	elif command -v open > /dev/null 2>&1; then \
		open http://localhost:8001; \
	elif command -v wslview > /dev/null 2>&1; then \
		wslview http://localhost:8001; \
	elif command -v cmd.exe > /dev/null 2>&1; then \
		cmd.exe /c start http://localhost:8001; \
	else \
		echo "❌ Could not detect browser launcher."; \
		echo "📍 Please open http://localhost:8001 manually"; \
	fi

docker-push:
	@echo "Building multi-arch image (amd64 + arm64)..."
	@echo "Note: M-Series Mac builds arm64-only images. Buildx creates both architectures for x86 server compatibility."
	docker buildx build --platform linux/amd64,linux/arm64 -f build/Dockerfile.app -t makomweb/momentum:latest . --push
	@echo "✓ Multi-arch image pushed to Docker Hub"
