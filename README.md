# Momentum

<p align="center">
  <img src="./assets/logo.png" alt="logo" width="300"/>
</p>


## Boot 🚀

```bash
# build images, install dependencies, start the stack
make boot
```

## Requirements

### Authentication via Strava OAuth

- dunno if it is OAuth 2.0 or OIDC - nevermind
- auth + refresh flow is already implemented in the respective CLI commands
  - persists token for a strava user in the cache
  - shows if a token is expired
  - fetching activities + activity streams works with this token
- missing features:
  - authentication via Symfony TWIG frontend
  - login screen
  - following Symfony best practices
  - uses existing implementation which is already used by the 2 CLI commands
  - UI with current Bootstrap styling
