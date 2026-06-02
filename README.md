# Momentum

<p align="center">
  <img src="./assets/logo.png" alt="logo" width="300"/>
</p>

## Quick Start

### Strava Account Setup

Momentum requires Strava API credentials to fetch and analyze your running activities.

1. Get credentials from [Strava API Settings](https://www.strava.com/settings/api)
2. Copy `.env.local.example` to `.env.local` 
3. Fill in `STRAVA_CLIENT_ID` and `STRAVA_CLIENT_SECRET`
4. Set redirect URI to: `http://localhost:8080/exchange-token`

### Bootstrap the application

```bash
# copy and configure Strava credentials
cp .env.local.example .env.local

# edit .env.local with your Strava Client ID and Secret
# ⚠️ never commit .env.local (it should be gitignored anyways)
vim .env.local

# boot the application
make boot
```
