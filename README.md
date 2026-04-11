# FrankenPHP template

A Docker Compose template for scaffolding PHP apps with FrankenPHP, MySQL 8, and Composer.

## Provides

- **FrankenPHP** app on port `8080`
- **MySQL 8** on port `3306`
- **Composer** service for dependency management and scaffolding
- `Makefile` targets for common setup and workflow tasks

## Steps

### Symfony or Laravel?

```bash
# Create a Symfony app:
make symfony

# Or Create a Laravel app:
make laravel
```
Both targets create `./app/Caddyfile` automatically after scaffolding.

### Boot 🚀

```bash
# build images, install dependencies, start the stack
make boot
```
