# Deployment Guide

This document covers Helm installation, Kubernetes cluster setup, and CI/CD automation for Momentum.

## Table of Contents

1. [Initial Cluster Setup](#initial-cluster-setup)
2. [Helm Installation](#helm-installation)
3. [GitLab CI/CD Automation](#gitlab-cicd-automation)
4. [Manual Deployment](#manual-deployment)
5. [Troubleshooting](#troubleshooting)

---

## Initial Cluster Setup

### Kubernetes Cluster Requirements

- K3s or Kubernetes 1.24+
- kubectl access configured
- KUBECONFIG environment variable set

### Network Policies

Apply cluster-wide network policies (**one-time setup**):

```bash
kubectl apply -f k8s/network-policy-egress.yaml
```

This allows pods to make outbound connections for:
- DNS resolution (UDP port 53)
- External APIs (TCP port 443 - HTTPS)
- HTTP webhooks (TCP port 80)
- Blocks AWS metadata access for security (169.254.169.254)

---

## Helm Installation

### Initial Installation (First Time)

#### Prerequisites

1. **Helm CLI installed** on your deployment machine:
   ```bash
   curl -fsSL https://raw.githubusercontent.com/helm/helm/main/scripts/get-helm-3 | bash
   ```

2. **SSH access** to your cluster node (for K3s)

3. **Docker image pushed** to registry (e.g., Docker Hub)

#### First-Time Install

```bash
# On your local machine or CI/CD runner:

KUBECONFIG=/etc/rancher/k3s/k3s.yaml helm install momentum ./helm/momentum \
  --set-string app.image=makomweb/momentum:latest \
  --set-string app.secret="$(openssl rand -hex 32)" \
  --set-string strava.clientId=YOUR_CLIENT_ID \
  --set-string strava.clientSecret=YOUR_CLIENT_SECRET \
  --set-string strava.redirectUri=https://your-domain.com/exchange-token
```

Or with **Ingress + Let's Encrypt (Traefik)**:

```bash
KUBECONFIG=/etc/rancher/k3s/k3s.yaml helm install momentum ./helm/momentum \
  --set-string app.image=makomweb/momentum:latest \
  --set-string app.secret="$(openssl rand -hex 32)" \
  --set-string strava.clientId=YOUR_CLIENT_ID \
  --set-string strava.clientSecret=YOUR_CLIENT_SECRET \
  --set-string strava.redirectUri=https://your-domain.com/momentum/exchange-token \
  --set-string ingress.enabled=true \
  --set-string ingress.hostname=your-domain.com
```

#### Verify Installation

```bash
export KUBECONFIG=/etc/rancher/k3s/k3s.yaml
kubectl get pods -n default
kubectl get svc -n default
helm list
```

---

## GitLab CI/CD Automation

### Setup

#### 1. Generate Deployment SSH Key

```bash
# On your local machine:
ssh-keygen -t ed25519 -f ~/.ssh/hetzner_deploy -C "gitlab-deploy"
```

#### 2. Add Public Key to Hetzner Server

```bash
# Copy public key to clipboard:
cat ~/.ssh/hetzner_deploy.pub | pbcopy

# Or manually:
cat ~/.ssh/hetzner_deploy.pub
```

Then add it to your Hetzner server via web frontend under SSH Keys.

#### 3. Add Private Key to GitLab CI/CD Variables

1. Go to **GitLab Project > Settings > CI/CD > Variables**
2. Click **Add variable**
3. Fill in:
   - **Name**: `SSH_PRIVATE_KEY`
   - **Value**: Paste the private key (from `~/.ssh/hetzner_deploy`)
   - **Type**: `File`
   - **Protected**: ✅ (checked)
   - **Masked**: ❌ (unchecked - File type handles multi-line content)
4. Click **Add variable**

#### 4. Verify Required CI/CD Variables

Ensure these are set in **GitLab Project > Settings > CI/CD > Variables**:

| Variable | Purpose | Example |
|----------|---------|---------|
| `SSH_PRIVATE_KEY` | SSH private key (File) | (contents of ~/.ssh/hetzner_deploy) |
| `DEPLOY_HOST` | Hetzner server IP/hostname | `123.45.67.89` or `server.example.com` |
| `DEPLOY_USER` | SSH user | `root` or `ubuntu` |
| `DOCKERHUB_USERNAME` | Docker Hub user | `makomweb` |
| `DOCKERHUB_TOKEN` | Docker Hub access token | (from Docker Hub settings) |

**Protected?** Yes - these should only be available on protected branches (master).

### Deployment Workflow

```
Merge to master
    ↓
GitLab CI Pipeline runs:
  1. qa stage        - Run tests/lint
  2. build stage     - Build & push Docker image
  3. deploy stage    - **Manual trigger** - updates Helm deployment
    ↓
Helm upgrade --reuse-values (only updates image + pullPolicy)
    ↓
New pods deployed with latest code
```

### Manual Deployment Trigger

1. Go to **GitLab Project > CI/CD > Pipelines**
2. Find the pipeline for your merge to master
3. Click **play button** on `deploy-hetzner` job
4. Wait for deployment to complete

### CI/CD Pipeline Configuration

See `.gitlab-ci.yml` for details. Key deployment command:

```bash
helm upgrade momentum /tmp/helm/momentum \
  --reuse-values \
  --set-string app.image=makomweb/momentum:$CI_COMMIT_SHA \
  --set-string app.pullPolicy=Always
```

**Why `--reuse-values`?**
- Only updates Docker image and pull policy
- Preserves all existing configuration (secrets, Strava credentials, etc.)
- Simpler and safer than re-setting all values each deploy
- Works because deployment is already running on the server

---

## Manual Deployment

### From Your Local Machine

Useful for testing or manual updates:

#### Prerequisites

```bash
export KUBECONFIG=/path/to/k3s.yaml  # Or set via ssh tunnel
helm repo update
```

#### Update Running Deployment

```bash
helm upgrade momentum ./helm/momentum \
  --reuse-values \
  --set-string app.image=makomweb/momentum:$COMMIT_SHA \
  --set-string app.pullPolicy=Always
```

#### Update With New Configuration Values

```bash
helm upgrade momentum ./helm/momentum \
  --reuse-values \
  --set-string strava.redirectUri=https://new-domain.com/exchange-token
```

#### Full Example (Change Multiple Values)

```bash
helm upgrade momentum ./helm/momentum \
  --set-string app.image=makomweb/momentum:latest \
  --set-string app.pullPolicy=Always \
  --set-string strava.redirectUri=https://your-domain.com/momentum/exchange-token
```

---

## Troubleshooting

### Check Deployment Status

```bash
export KUBECONFIG=/etc/rancher/k3s/k3s.yaml

# Pod status
kubectl get pods
kubectl describe pod <pod-name>

# Logs
kubectl logs <pod-name>
kubectl logs <pod-name> --tail=100 -f

# Helm release status
helm status momentum
helm history momentum
```

### Common Issues

#### Pod in CrashLoopBackOff

```bash
# Check logs for errors:
kubectl logs <pod-name>

# Check events:
kubectl describe pod <pod-name>

# Common causes:
# - Database connection string incorrect
# - Missing environment variables
# - Image pull failed
```

#### Helm Upgrade Failed

```bash
# Rollback to previous release:
helm rollback momentum

# Or specific release number:
helm rollback momentum 3
```

#### SSH Connection Issues (GitLab CI)

```bash
# Test SSH access manually:
ssh -i ~/.ssh/hetzner_deploy user@server "helm list"

# Check known_hosts:
ssh-keyscan -H your-server >> ~/.ssh/known_hosts
```

#### Network Policy Blocking Traffic

```bash
# Verify policy applied:
kubectl get networkpolicies

# Check policy:
kubectl describe networkpolicy allow-external-egress

# Temporarily disable for debugging:
kubectl delete networkpolicy allow-external-egress
```

### Helm Chart Issues

#### Verify Chart Configuration

```bash
# See all rendered values:
helm template momentum ./helm/momentum

# See what will be deployed:
helm diff upgrade momentum ./helm/momentum --reuse-values
```

#### Update Helm Chart

If you modify `helm/momentum/values.yaml` or templates:

```bash
# Helm automatically picks up changes on next upgrade
helm upgrade momentum ./helm/momentum --reuse-values
```

---

## Environment Variables

### Application Configuration

Managed via:
1. **Helm ConfigMap** (app configuration):
   - `APP_ENV`
   - `APP_VERSION`
   - `MESSENGER_TRANSPORT_DSN`
   - `CACHE_URL`
   - `SECURE_COOKIE`

2. **Helm Secret** (sensitive values):
   - `DATABASE_URL`
   - `APP_SECRET`
   - `STRAVA_CLIENT_ID`
   - `STRAVA_CLIENT_SECRET`
   - `STRAVA_REDIRECT_URI`

### Viewing Current Configuration

```bash
export KUBECONFIG=/etc/rancher/k3s/k3s.yaml

# ConfigMap values
kubectl get configmap momentum-config -o yaml

# Secret values (encrypted in Kubernetes)
kubectl get secret momentum-secret -o yaml
```

---

## Rollback & Recovery

### Rollback to Previous Helm Release

```bash
# List release history:
helm history momentum

# Rollback to previous:
helm rollback momentum

# Rollback to specific revision:
helm rollback momentum 3
```

### Manual Pod Restart

```bash
# Force restart all pods:
kubectl rollout restart deployment/momentum-app

# Check rollout status:
kubectl rollout status deployment/momentum-app
```

---

## Security Notes

- `DEPLOY_SSH_KEY` should be **protected** (master branch only) in GitLab
- SSH key permissions must be `600` (script handles this)
- `APP_SECRET` should be a strong random value (generated with `openssl rand -hex 32`)
- Keep credentials in Kubernetes Secrets, not in Helm values
- Use HTTPS only in production (`SECURE_COOKIE=true`)
- Network policies restrict egress to required ports only

---

## References

- [Helm Documentation](https://helm.sh/docs/)
- [Kubernetes Documentation](https://kubernetes.io/docs/)
- [K3s Documentation](https://docs.k3s.io/)
- [Traefik Ingress Controller](https://doc.traefik.io/traefik/)
