# Copilot Instructions for Run Forge

This guide helps Copilot sessions work effectively in this Symfony 8 application that integrates with the Strava API to track and analyze running activities.

## Quick Start

```bash
# First time setup
make boot

# Start the stack
make up

# Stop the stack
make down

# Open shell in app container
make shell

# View logs
make logs
```

The app runs at http://localhost:8080 with MySQL database at http://localhost:8085 (Adminer).

## Architecture

The application is organized into logical layers following a clean architecture pattern, enforced by Deptrac:

### Core Layer (`App\RunForge`)
- **Business logic** independent of frameworks and external dependencies
- Contains domain models for running metrics:
  - `Average`: Calculates average values
  - `MovingAverage`: Computes moving averages
  - `InitialAverage`: Provides initial average calculations
- Pure domain logic without Symfony or external dependencies

### Supporting Layer (`App\Strava`)
- **Strava OAuth2 integration** and API communication
- `Client`: HTTP client for Strava API
- `Configuration`: OAuth2 configuration (client ID, secret, redirect URI)
- `TokenCache`: Persists OAuth tokens locally
- `Synchronizer`: Syncs activities from Strava to the database
- `ActivityStreamCache`: Caches activity stream data (elevation, pace, etc.)
- Depends on Core layer

### Generic Layer
- Framework and standard library abstractions (Symfony, PSR, PHPUnit)
- Available to all layers

### Integration Layer
- **Controllers**: HTTP endpoints for web UI
- **Commands**: CLI commands for OAuth flow and data sync
- **Security**: Authentication middleware, providers, logout handlers
- **Entities & Repositories**: Database access layer (Doctrine ORM)
- **Middleware**: Request/response handling

### Test Layer (`App\Tests`)
- Unit tests validating Core and Supporting layer behavior
- Can depend on all other layers

## Build, Test & Lint Commands

All commands use Docker and are run via Make:

### QA Commands

```bash
# Run full QA suite (the most important one)
make qa

# Individual checks:
make test        # Run PHPUnit tests only
make sa          # Static analysis (PHPStan at level max)
make cs          # Fix code style (Symfony style rules)
make arch        # Architecture validation (Deptrac layer checks)
```

To run a **single test**:
```bash
docker compose run --rm composer bin/phpunit tests/Unit/RunForge/MovingAverageTest.php
```

### Development Commands

```bash
make composer-install      # Install dependencies
make maintain              # Update dependencies (composer update -W)
make composer-require PKG=vendor/package  # Add a package
make cc                    # Clear all caches (app + pool)
make cache-clear           # Clear only app cache
make cache-pool-clear      # Clear only pool cache
```

### Database Commands

```bash
make create-database  # Create database if not exists
make create-schema    # Create tables from entity definitions
```

## Key Conventions

### Code Style

- **PHP-CS-Fixer** enforces Symfony style rules
- Test method names use **snake_case** (not camelCase)
- No PSR-12 enforcement; Symfony rules override default PSR
- Run `make cs` automatically before commits

### Testing

- Unit tests in `tests/Unit/` under PSR-4 namespace `App\Tests\`
- PHPUnit configuration in `phpunit.dist.xml` with strict settings:
  - Fails on deprecations, notices, and warnings
  - All notices/warnings treated as test failures
  - Code coverage includes `src/` directory
- Bootstrap file at `tests/bootstrap.php` sets up test environment

### Static Analysis

- **PHPStan** runs at maximum level (8/8)
- Baseline issues tracked in `phpstan-baseline.neon`
- All paths checked: `bin/`, `config/`, `public/`, `src/`, `tests/`

### Architecture

- **Deptrac** enforces layering in `deptrac.yaml`
- Layer dependencies:
  - Core: isolated, no external dependencies
  - Supporting: can depend on Core + Generic
  - Tests: can depend on all layers
  - Violations trigger failures in `make arch`

### Entity/Repository Pattern

- Entities use Doctrine ORM attributes (PHP 8 style)
- Repositories extend `ServiceEntityRepository`
- Migrations tracked in `migrations/` directory
- Schema updates via `doctrine:schema:update --force`

### Strava Integration

- **OAuth2 flow**: User authorizes app, receives token
- **Token persistence**: Cached locally in `var/` directory
- **Token refresh**: Automatic during API calls if expired
- Configuration via environment variables:
  - `STRAVA_CLIENT_ID`
  - `STRAVA_CLIENT_SECRET`
  - `STRAVA_REDIRECT_URI`

### Services & Dependency Injection

- Auto-wired services in `config/services.yaml`
- All `src/` classes automatically registered as services
- Autowire enabled by default
- Autoconfigure enabled for commands, subscribers, etc.

## Environment Setup

### Local Development

Environment files:
- `.env` - Main configuration
- `.env.dev` - Development overrides
- `.env.test` - Test environment (APP_ENV=test)

Key variables:
```
DATABASE_URL=mysql://root:secret@db:3306/app?serverVersion=8.4&charset=utf8mb4
STRAVA_CLIENT_ID=...
STRAVA_CLIENT_SECRET=...
STRAVA_REDIRECT_URI=http://localhost:8080/auth/callback
```

### Database

- MySQL 8.4 in Docker
- Healthcheck ensures container is ready before app starts
- Adminer UI available at http://localhost:8085 for manual inspection
- User: `root`, Password: `secret`

## Debugging & Development

- View running logs: `make logs` (follow mode)
- Access container shell: `make shell` then `cd /app`
- Console commands: `bin/console <command>`
- Debug route definitions: `bin/console debug:router`
- Cache files stored in `var/` (gitignored)
- PHP version: 8.4+ required

## File Structure

```
.github/
  copilot-instructions.md     # This file
app/
  bin/console                 # Symfony CLI
  config/                     # Service definitions, routing, packages
  public/                     # Web root (index.php)
  src/
    Command/                  # CLI commands
    Controller/               # HTTP controllers
    Entity/                   # Doctrine ORM entities
    Middleware/               # HTTP middleware
    Repository/               # Data access layer
    RunForge/                 # Core business logic
    Security/                 # Auth providers, handlers
    Strava/                   # Strava API integration
  tests/
    Unit/                     # Unit test suite
  templates/                  # Twig templates (HTML)
  var/                        # Cache, logs, uploaded files (gitignored)
  migrations/                 # Database migration files
  composer.json               # PHP dependencies
  phpunit.dist.xml            # Test configuration
  phpstan.neon.dist           # Static analysis config
  deptrac.yaml                # Architecture rules
  .php-cs-fixer.dist.php      # Code style config
build/
  Dockerfile.app              # Application container
  Dockerfile.composer         # Composer container
docker-compose.yaml           # Services: app, db, composer, adminer
Makefile                      # Task automation
```

## Common Tasks

### Adding a New Package

```bash
make composer-require PKG=symfony/form
```

This uses the composer service to safely add dependencies.

### Creating a Database Migration

```bash
make shell
bin/console make:migration DescribeYourChange
bin/console doctrine:migrations:migrate
```

### Adding a New Console Command

Commands go in `src/Command/` and extend `SymfonyStyle` for CLI output. They're automatically discovered.

### Adding a New Controller Action

Controllers in `src/Controller/` use `#[Route]` attributes for routing. They're automatically discovered.
