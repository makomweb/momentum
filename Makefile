.PHONY: help build init symfony laravel up show-hint shell down composer-install maintain composer-update composer-require caddyfile logs open


help:
	@echo "Available targets:"
	@echo "  boot                  Build images, install dependencies, start the stack"
	@echo "  up                    Start the stack"
	@echo "  down                  Stop the stack"
	@echo "  shell                 Open shell on app container"
	@echo "  show-hint             Print local app URLs"
	@echo "  build                 Build Docker images"
	@echo "  composer-install      Run composer install"
	@echo "  maintain              Run composer update -W"
	@echo "  composer-require      Add a package (use PKG=vendor/package)"
	@echo "  caddyfile             Create ./app/Caddyfile"
	@echo "  logs                  View app logs"
	@echo "  open                  Open the app in a browser"


boot: build composer-install up

build:
	@echo "Building Docker images..."
	docker-compose build

up:
	@echo "Starting the stack..."
	docker-compose up -d
	@echo "Stack started!"
	@$(MAKE) show-hint

show-hint:
	@echo "Application available at http://localhost:8080"
	@echo "MySQL available at localhost:3306"
	@echo "To view logs: make logs"

shell:
	@echo "Open shell on app container..."
	docker compose exec -it app bash

down:
	@echo "Stopping the stack..."
	docker-compose down

composer-install:
	@echo "Running composer install..."
	docker-compose run --rm composer install

maintain:
	@echo "Running composer update -W..."
	docker-compose run --rm composer update -W

composer-update: maintain

composer-require:
	@test -n "$(PKG)" || (echo "Error: specify package with PKG=vendor/package"; exit 1)
	@echo "Adding package $(PKG)..."
	docker-compose run --rm composer require $(PKG)

logs:
	docker-compose logs -f app

open:
	@if command -v xdg-open > /dev/null 2>&1; then \
		xdg-open http://localhost:8080 2>/dev/null & \
	elif command -v open > /dev/null 2>&1; then \
		open http://localhost:8080; \
	elif command -v wslview > /dev/null 2>&1; then \
		wslview http://localhost:8080; \
	elif command -v cmd.exe > /dev/null 2>&1; then \
		cmd.exe /c start http://localhost:8080; \
	else \
		echo "❌ Could not detect browser launcher."; \
		echo "📍 Please open http://localhost:8080 manually"; \
	fi
