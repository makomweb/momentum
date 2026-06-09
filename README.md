# Momentum

<p align="center">
  <img src="./assets/logo.png" alt="logo" width="300"/>
</p>

Momentum tracks and analyzes your running activities from Strava in real-time. It computes moving averages and performance metrics to help you understand your training progress and identify trends.

Built with Symfony 8 and clean architecture principles. Integrates with Strava OAuth2 API, stores data in MySQL, caches with Valkey, and deploys on Kubernetes with Helm.

## Quick Start

### Development

**Prerequisites:** Docker, Docker Compose, Make

1. Get [Strava API credentials](https://www.strava.com/settings/api)
2. Copy `.env.local.example` to `.env.local` and fill in `STRAVA_CLIENT_ID`, `STRAVA_CLIENT_SECRET`, and set redirect URI to `http://localhost:8080/exchange-token`
3. Run `make boot` to start the stack

### Production

**Prerequisites:** Kubernetes cluster (K3s, EKS, etc.), kubectl, Helm 3

Momentum deploys on Kubernetes with automatic HTTPS via Traefik's native ACME (Let's Encrypt DNS-01).

See [DEPLOYMENT.md](./DEPLOYMENT.md) for:
- Detailed Helm installation instructions
- Kubernetes cluster setup and network policies
- CI/CD automation with GitLab
- Troubleshooting and rollback procedures
- Security best practices
