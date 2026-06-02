# Architecture

## Overview

Momentum follows a clean architecture pattern organized into logical layers, enforced by Deptrac.

### Layers

#### Core Layer (`App\Momentum`)
- Business logic independent of frameworks
- Domain models for running metrics:
  - `Average`: Calculates average values
  - `MovingAverage`: Computes moving averages
  - `InitialAverage`: Provides initial average calculations
- Pure domain logic without Symfony dependencies

#### Supporting Layer (`App\Strava`)
- Strava OAuth2 integration and API communication
- `Client`: HTTP client for Strava API
- `Configuration`: OAuth2 configuration
- `TokenCache`: Persists OAuth tokens
- `Synchronizer`: Syncs activities from Strava
- `ActivityStreamCache`: Caches activity stream data
- Depends on Core layer

#### Integration Layer
- Controllers: HTTP endpoints
- Commands: CLI commands
- Security: Authentication middleware
- Entities & Repositories: Database access (Doctrine ORM)
- Middleware: Request/response handling

## Technology Stack

- **Framework**: Symfony 8
- **Database**: MySQL 8.4
- **ORM**: Doctrine
- **External API**: Strava API v3
- **Testing**: PHPUnit
- **Static Analysis**: PHPStan
- **Code Style**: PHP-CS-Fixer
- **Architecture Validation**: Deptrac
