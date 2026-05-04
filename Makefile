.PHONY: help build init symfony laravel up hint shell down composer-install maintain composer-update composer-require caddyfile logs open


help:
	@echo "Available targets:"
	@echo "  boot                  Build images, install dependencies, start the stack, init database"
	@echo "  up                    Start the stack"
	@echo "  down                  Stop the stack"
	@echo "  shell                 Open shell on app container"
	@echo "  hint                  Print local app URLs"
	@echo "  build                 Build Docker images"
	@echo "  composer-install      Run composer install"
	@echo "  maintain              Run composer update -W"
	@echo "  composer-require      Add a package (use PKG=vendor/package)"
	@echo "  caddyfile             Create ./app/Caddyfile"
	@echo "  logs                  View app logs"
	@echo "  open                  Open the app in a browser"


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
	@echo "App at http://localhost:8080"
	@echo "Dashboard at http://localhost:8000 (links to all services)"
	@echo "To view logs: make logs"

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
	docker compose run --rm composer update -W

composer-update: maintain

composer-require:
	@test -n "$(PKG)" || (echo "Error: specify package with PKG=vendor/package"; exit 1)
	@echo "Adding package $(PKG)..."
	docker compose run --rm composer require $(PKG)

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
	docker compose run --rm composer sa

cs:
	@echo "Fix code style..."
	docker compose run --rm composer cs

test:
	@echo "Run tests..."
	docker compose run --rm composer test

arch:
	@echo "Run architecture tests..."
	docker compose run --rm composer arch

qa:
	@echo "Run QA scripts..."
	docker compose run --rm composer qa

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
		xdg-open http://localhost:8000 2>/dev/null & \
	elif command -v open > /dev/null 2>&1; then \
		open http://localhost:8000; \
	elif command -v wslview > /dev/null 2>&1; then \
		wslview http://localhost:8000; \
	elif command -v cmd.exe > /dev/null 2>&1; then \
		cmd.exe /c start http://localhost:8000; \
	else \
		echo "❌ Could not detect browser launcher."; \
		echo "📍 Please open http://localhost:8000 manually"; \
	fi
