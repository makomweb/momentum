---
name: php-execution
description: Comprehensive guide for executing PHP code, running tests, console commands, and managing the development environment in the Momentum project.
---

# PHP Execution Skill

This skill provides comprehensive guidance on executing PHP code and managing the development environment in the Momentum project, which uses Symfony 8, PHPUnit, and Docker.

## Quick Reference

| Task | Command | Purpose |
|------|---------|---------|
| Run all tests | `make test` | Execute entire PHPUnit test suite |
| Run single test file | `docker compose run --rm composer bin/phpunit tests/Unit/TestName.php` | Test specific component |
| Run specific test method | `docker compose run --rm composer bin/phpunit tests/Unit/TestName.php::test_method_name` | Debug individual test |
| Static analysis | `make sa` | Run PHPStan level 8 analysis |
| Fix code style | `make cs` | Auto-format with PHP-CS-Fixer |
| Validate architecture | `make arch` | Check Deptrac layer violations |
| Full QA suite | `make qa` | Run tests + SA + style + architecture checks |
| Open shell | `make shell` | Interactive bash in app container |
| Install dependencies | `make composer-install` | Install PHP packages |
| Add package | `make composer-require PKG=vendor/package` | Add new dependency |
| Database setup | `make create-database && make create-schema` | Initialize database |
| View logs | `make logs` | Follow container logs |

## When to Use This Skill

Use this skill when you need to:
- Execute or debug PHP code
- Run PHPUnit tests with various configurations
- Execute Symfony console commands
- Set up or manage the Docker development environment
- Troubleshoot PHP execution issues
- Understand the Docker container structure
- Manage PHP dependencies

**Note**: For QA-focused tasks (running the full QA suite, code review, static analysis), refer to the `quality-assurance` skill.

## ⚠️ CRITICAL: Understanding Docker Service Execution

**This is the most important distinction to understand:**

### The Two Docker Services

| Service | Purpose | Use For | Command Pattern |
|---------|---------|---------|-----------------|
| **`app`** | PHP-FPM runtime (the running application) | Console commands, web server, HTTP testing | `docker compose exec app bin/console ...` or `make shell` |
| **`composer`** | PHP-CLI with Composer (development/build environment) | PHPUnit tests, Composer operations, code analysis | `docker compose run --rm composer bin/phpunit ...` |

### Decision Tree: Which Service to Use?

```
Am I running a PHP command?
├─ PHPUnit tests? → Use COMPOSER service: docker compose run --rm composer bin/phpunit
├─ Composer commands (install, require, update)? → Use COMPOSER service: docker compose run --rm composer composer ...
├─ Code analysis (PHPStan, PHP-CS-Fixer)? → Use COMPOSER service: docker compose run --rm composer bin/...
├─ Symfony console commands (bin/console)? → Use APP service: docker compose exec app bin/console ...
└─ Need interactive shell? → Use APP service: make shell or docker compose exec app bash
```

### Examples: Composer Service (Testing & Build)

```bash
# ✅ Run tests - COMPOSER service
docker compose run --rm composer bin/phpunit tests/Unit/TestName.php

# ✅ Install dependencies - COMPOSER service
docker compose run --rm composer composer install

# ✅ Add a package - COMPOSER service
docker compose run --rm composer composer require vendor/package

# ✅ Run static analysis - COMPOSER service
docker compose run --rm composer bin/phpstan analyze
```

### Examples: App Service (Runtime & Console)

```bash
# ✅ Run Symfony console command - APP service
docker compose exec app bin/console list

# ✅ Run migrations - APP service
docker compose exec app bin/console doctrine:migrations:migrate

# ✅ Clear caches - APP service
docker compose exec app bin/console cache:clear

# ✅ Interactive shell - APP service (via make helper)
make shell
cd /app
bin/console debug:router
```

### Why Two Services?

- **`composer` (CLI)**: Stateless, isolated environment for testing and dependency management. Each `docker compose run` creates a fresh container, then removes it. Safe for concurrent operations.
- **`app` (FPM)**: Persistent runtime that stays up throughout development. Used for console commands that interact with the running application, database, and cache layers.

### Command Helpers

To avoid memorizing this, use the Makefile helpers:

```bash
make test              # Automatically uses composer service
make shell             # Automatically uses app service  
make composer-require  # Automatically uses composer service
```

---

## Running Tests

### Accessing the Test Environment

#### Prerequisites

Ensure the Docker stack is running:
```bash
make up
```

#### Running All Tests

Execute the entire PHPUnit test suite:
```bash
make test
```

This runs all tests in `tests/Unit/` and reports coverage.

### Running Specific Tests

#### Single Test File

To test a specific component:
```bash
docker compose run --rm composer bin/phpunit tests/Unit/MovingAverageTest.php
```

#### Specific Test Method

To debug a single test:
```bash
docker compose run --rm composer bin/phpunit tests/Unit/MovingAverageTest.php::test_calculates_average
```

#### Multiple Files by Pattern

Run tests matching a pattern:
```bash
docker compose run --rm composer bin/phpunit tests/Unit/ --filter "Moving"
```

### Test Configuration

- **Test Location**: `tests/Unit/` with namespace `App\Tests\`
- **PHPUnit Config**: `phpunit.dist.xml` with strict settings
- **Bootstrap**: `tests/bootstrap.php` initializes test environment
- **Test Naming**: Methods use **snake_case** (e.g., `test_should_calculate_average`)
- **Coverage**: Includes `src/` directory in reports
- **Strict Mode**: Treats deprecations, notices, and warnings as failures

### Debugging Tests

#### With Verbose Output

```bash
docker compose run --rm composer bin/phpunit tests/Unit/ -v
```

#### With Code Coverage

```bash
docker compose run --rm composer bin/phpunit --coverage-html=coverage
```

Then check `coverage/index.html` in your browser (after copying from container if needed).

#### With Debug Output

For a test that's failing, add verbose and debug flags:
```bash
docker compose run --rm composer bin/phpunit tests/Unit/TestName.php -vvv --debug
```

## Executing Console Commands

### Interactive Shell Access

The easiest way to run multiple console commands:

```bash
make shell
```

Inside the shell:
```bash
cd /app
bin/console command:name [arguments] [options]
```

### Direct Command Execution

Run a single command from your host machine:

```bash
docker compose exec app bin/console command:name [arguments]
```

### Common Symfony Commands

```bash
# List all available commands
bin/console list

# Show command help
bin/console command:name --help

# Debug routes
bin/console debug:router

# View services
bin/console debug:container

# Clear caches
bin/console cache:clear

# Database migrations
bin/console make:migration
bin/console doctrine:migrations:migrate
bin/console doctrine:migrations:status

# Run custom application commands
bin/console app:your-custom-command
```

### Custom Commands

Custom commands are auto-discovered from `src/Command/` directory:

1. Commands extend `Symfony\Component\Console\Command\Command`
2. Implement `execute()` method with command logic
3. Use `SymfonyStyle` for formatted CLI output
4. Auto-registered via service container

Example execution:
```bash
bin/console app:sync-strava-data
bin/console app:generate-report
```

## Docker Environment Setup

### Services Overview

The project uses Docker Compose with these services:

| Service | Image | Purpose | Access |
|---------|-------|---------|--------|
| `app` | PHP 8.4-FPM | Application runtime | http://localhost:8080 |
| `db` | MySQL 8.4 | Database | Internal (no direct access) |
| `composer` | PHP 8.4-CLI | Dependency management | `docker compose exec composer` |
| `adminer` | Adminer latest | Database UI | http://localhost:8085 |

### Environment Variables

Configuration in `.env` and `.env.dev`:

```
DATABASE_URL=mysql://root:secret@db:3306/app?serverVersion=8.4&charset=utf8mb4
APP_ENV=dev
APP_DEBUG=1
STRAVA_CLIENT_ID=...
STRAVA_CLIENT_SECRET=...
STRAVA_REDIRECT_URI=http://localhost:8080/auth/callback
```

### Starting the Stack

```bash
# First-time setup (installs dependencies)
make boot

# Start services
make up

# Stop services
make down

# View real-time logs
make logs
```

### Container Operations

#### Open Interactive Shell

```bash
make shell
```

Then interact with the app directly:
```bash
cd /app
bin/console list
php -v
composer show
```

#### Run Commands Without Shell

```bash
docker compose exec app php -v
docker compose exec app bin/console list
```

#### Execute Composer Commands

```bash
# Install dependencies
make composer-install

# Update all dependencies
make maintain

# Add a package
make composer-require PKG=symfony/form
```

### Cache Management

```bash
# Clear all caches
make cc

# Clear only app cache
make cache-clear

# Clear only pool cache
make cache-pool-clear
```

## Database Operations

### Initial Setup

```bash
make create-database   # Create the database schema
make create-schema     # Create tables from entities
```

### Running Migrations

```bash
# Inside shell:
make shell
cd /app

# View migration status
bin/console doctrine:migrations:status

# Create a new migration
bin/console make:migration DescribeChange

# Execute pending migrations
bin/console doctrine:migrations:migrate

# Rollback last migration
bin/console doctrine:migrations:migrate prev
```

### Database Access

**Adminer UI**: http://localhost:8085
- **Database**: app
- **User**: root
- **Password**: secret

### Direct Database Queries

```bash
docker compose exec db mysql -uroot -psecret app
```

Then use standard MySQL commands.

## Troubleshooting PHP Execution

### Issue: "Container not found" or Docker errors

**Causes**: Services not running, configuration issues

**Solutions**:
```bash
# Start the stack
make up

# Wait for containers to be healthy
docker compose ps

# Check service logs
docker compose logs app
```

### Issue: Tests fail with PHP errors/warnings

**Causes**: Strict PHPUnit configuration treats all notices as failures (intentional)

**Solutions**:
1. Fix the underlying PHP issue in your code
2. Or update the baseline in `phpunit.dist.xml` if it's a known limitation
3. Run tests with verbose output to see details: `make test -vvv`

### Issue: "Database doesn't exist" errors

**Causes**: Database not initialized

**Solutions**:
```bash
make create-database
make create-schema

# Or manually via Adminer UI
```

### Issue: Composer dependency conflicts

**Causes**: Incompatible package versions

**Solutions**:
```bash
# Update all dependencies safely
make maintain

# Or reset and reinstall
docker compose run --rm composer composer install --no-interaction
```

### Issue: Code style or static analysis failures

**Causes**: Code doesn't follow project conventions

**Solutions**:
```bash
# Auto-fix code style
make cs

# Review and fix static analysis issues
make sa

# Update PHPStan baseline if needed
docker compose run --rm composer bin/phpstan analyze --generate-baseline=phpstan-baseline.neon
```

### Issue: Port already in use (8080, 8085)

**Causes**: Services still running or port conflicts

**Solutions**:
```bash
# Stop all services
make down

# Or kill processes on specific ports
lsof -ti :8080 | xargs kill -9
lsof -ti :8085 | xargs kill -9

# Then restart
make up
```

### Issue: "Permission denied" errors

**Causes**: File permission issues in Docker volumes

**Solutions**:
```bash
# Fix ownership in container
docker compose exec app chown -R www-data:www-data /app/var

# Or rebuild container
docker compose down
docker compose build --no-cache
make up
```

### Debug Mode

For detailed debugging output:

```bash
# Run with very verbose output
docker compose run --rm composer bin/phpunit -vvv tests/Unit/

# Run command with debug flag
docker compose exec app bin/console command:name --debug

# Monitor container in real-time
docker compose logs -f app

# Inspect container processes
docker compose exec app ps aux
```

## PHP Version and Extensions

This project requires:
- **PHP Version**: 8.4+
- **Key Extensions**: PDO MySQL, JSON, Reflection, SPL, Standard

Verify installed extensions:
```bash
make shell
php -m
php -v
```

## Related Resources

- **Copilot Instructions**: `/app/app/.github/copilot-instructions.md` - Full project guide
- **Quality Assurance Skill**: For QA-focused tasks like running full test suite
- **Project README**: `README.md` - Project overview and setup
- **PHP Documentation**: https://www.php.net/manual/
- **Symfony Documentation**: https://symfony.com/doc/
- **PHPUnit Documentation**: https://phpunit.de/documentation.html
