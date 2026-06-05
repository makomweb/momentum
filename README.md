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

1. Get [Strava API credentials](https://www.strava.com/settings/api)
2. Register your domain and point nameservers to your DNS provider (e.g., Hetzner DNS)
3. Generate a Symfony app secret: `openssl rand -hex 32`
4. Install via Helm:

**Basic Installation (NodePort only):**
```bash
helm install momentum ./helm/momentum \
  --set app.pullPolicy=Always \
  --set app.secret="$(openssl rand -hex 32)" \
  --set strava.clientId=YOUR_CLIENT_ID \
  --set strava.clientSecret=YOUR_CLIENT_SECRET \
  --set strava.redirectUri=http://YOUR_IP:31228/exchange-token
```

**With Ingress & HTTPS (Traefik DNS-01 + Let's Encrypt):**
```bash
helm install momentum ./helm/momentum \
  --set app.pullPolicy=Always \
  --set app.secret="$(openssl rand -hex 32)" \
  --set ingress.enabled=true \
  --set ingress.hostname=www.your-domain.com \
  --set strava.clientId=YOUR_CLIENT_ID \
  --set strava.clientSecret=YOUR_CLIENT_SECRET \
  --set strava.redirectUri=https://www.your-domain.com/exchange-token
```

**Note:** Traefik's ACME resolver is automatically configured on K3s clusters with Hetzner DNS provider. DNS records must be created in your DNS provider for domain verification.

5. Verify deployment: `kubectl get pods` and `kubectl get ingress`
