# Rate Limiting Implementation

This document explains the rate limiting system implemented for the Momentum backend.

## Overview

The application uses Symfony's built-in Rate Limiter component with a **two-tier hybrid approach**:

1. **Per-IP Compound Limiter** (per client IP)
   - 40 requests per 10 minutes (fixed window)
   - 5 tokens refill per 30 seconds, max 10 (token bucket)
   - **BOTH must pass** for request to be accepted

2. **Global Burst Limiter** (all IPs combined)
   - 200 tokens refill per 1 minute, max 500
   - Protects against distributed attacks

## Configuration

### Rate Limiter Configuration (`config/packages/rate_limiter.yaml`)

```yaml
framework:
    rate_limiter:
        per_ten_minutes:
            policy: 'fixed_window'
            limit: 40
            interval: '10 minutes'
        
        per_thirty_seconds:
            policy: 'token_bucket'
            limit: 10
            rate:
                amount: 5
                interval: '30 seconds'
        
        per_ip:
            policy: 'compound'
            limiters: [per_ten_minutes, per_thirty_seconds]
        
        global_burst:
            policy: 'token_bucket'
            limit: 500
            rate:
                amount: 200
                interval: '1 minute'
```

## How It Works

### Request Flow

```
Request arrives
    ↓
Per-IP Check (Compound):
  ├─ Has used <40 requests in 10 minutes? ✅ Continue
  └─ AND has token available (max 10, refill 5/30s)? ✅ Continue
    ↓
Global Burst Check:
  └─ Has token available (max 500, refill 200/1min)? ✅ Continue
    ↓
Request succeeds
```

### What Each Limiter Does

**Per-Ten-Minutes (Fixed Window):**
- Prevents sustained abuse
- Hard limit of 40 requests per 10-minute window
- Resets completely every 10 minutes
- Example: User making 40 requests, must wait up to 10 minutes for next batch

**Per-Thirty-Seconds (Token Bucket):**
- Allows short bursts
- 5 new tokens every 30 seconds (up to max 10)
- Smooth refill rate
- Example: User can make 10 quick requests, then limited to 5/30s

**Global Burst (Token Bucket):**
- Protects the entire server
- 200 new tokens every 1 minute (up to max 500)
- Catches distributed attacks where many IPs respect per-IP limits but collectively overload
- Example: 100 different IPs each staying under 40 req/10min could still hit this

## Logging

Detailed logging for violations:

```php
// Per-IP limit exceeded
$this->logger->warning('Per-IP rate limit exceeded', [
    'client_ip' => '192.168.1.1',
    'method' => 'GET',
    'path' => '/activities',
    'limiter' => 'compound (per_ten_minutes + per_thirty_seconds)',
    'retry_after_seconds' => 45,
]);

// Global limit exceeded
$this->logger->warning('Global rate limit exceeded', [
    'client_ip' => '10.0.0.5',
    'method' => 'POST',
    'path' => '/api/sync',
    'limiter' => 'global_burst',
    'retry_after_seconds' => 60,
]);
```

### Viewing Logs

**Development:**
```bash
docker compose logs -f app | grep "rate limit exceeded"
```

**Production:** Check your logging system for:
- Level: `warning`
- Messages: "Per-IP rate limit exceeded" or "Global rate limit exceeded"
- Filter by `limiter` field to distinguish which limit was hit

## Response Behavior

When a rate limit is exceeded:

```
HTTP/1.1 429 Too Many Requests
Retry-After: 45
Content-Type: text/plain

Too Many Requests
```

The `Retry-After` header contains actual seconds until next token is available (calculated dynamically).

## Customization

### Adjust Per-IP Limits

Edit `config/packages/rate_limiter.yaml`:

```yaml
per_ten_minutes:
    limit: 100              # Increase sustained limit
    interval: '10 minutes'

per_thirty_seconds:
    limit: 20               # Increase burst limit
    rate:
        amount: 10          # Increase refill rate
        interval: '30 seconds'
```

### Adjust Global Burst

```yaml
global_burst:
    limit: 1000             # Increase max burst
    rate:
        amount: 500         # Increase refill rate
        interval: '1 minute'
```

### Add Per-Endpoint Rate Limiting

Create additional limiters for specific endpoints:

```yaml
framework:
    rate_limiter:
        # ... existing limiters ...
        
        auth_limiter:
            policy: 'fixed_window'
            limit: 5
            interval: '5 minutes'
```

Then create a new listener:

```php
#[AsEventListener(event: KernelEvents::REQUEST, priority: 45)]
final class AuthRateLimitListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        if (str_starts_with($event->getRequest()->getPathInfo(), '/auth/login')) {
            $clientIp = $event->getRequest()->getClientIp() ?? 'unknown';
            $limiter = $this->limiterAuth->create($clientIp);
            // ... check limit ...
        }
    }
}
```

## Testing

### Manual Testing

**Test per-IP burst limit:**
```bash
# Make 11 requests rapidly (per_thirty_seconds allows 10 max)
for i in {1..11}; do
  curl -s http://localhost:8080/ -w "%{http_code}\n"
done

# Expected: 10 × 200, 1 × 429
```

**Test per-IP sustained limit:**
```bash
# Make 41 requests over time (per_ten_minutes allows 40)
for i in {1..41}; do
  curl -s http://localhost:8080/ -w "%{http_code}\n"
  sleep 1  # Space them out
done

# Expected: 40 × 200, 1 × 429
```

**Test global burst limit:**
```bash
# Simulate 100 different IPs making requests
# Terminal 1-5: Each runs this loop with different sleep offset
for i in {1..200}; do
  curl -s http://localhost:8080/ 2>&1 | head -c1
  sleep 0.1
done

# When total requests hit 500 in 1 minute, all IPs get 429
```

### View Violations in Logs

```bash
docker compose logs app | grep "rate limit exceeded" | tail -10
```

## Monitoring & Metrics

### Key Metrics to Track

1. **Violations by limiter type**: Which limiter is being hit most?
2. **Top offending IPs**: Which IPs exceed per-IP limits?
3. **Global spike detection**: Is global burst being hit repeatedly?
4. **Peak hours**: When do violations spike?

### Example Log Analysis

```bash
# Count violations per limiter
docker compose logs app | grep "rate limit exceeded" | grep -oP '(?<="limiter": ")[^"]+' | sort | uniq -c

# Top 10 offending IPs
docker compose logs app | grep "Per-IP rate limit" | grep -oP '(?<="client_ip": ")[^"]+' | sort | uniq -c | sort -rn | head -10

# Global violations over time
docker compose logs app | grep "Global rate limit exceeded" | wc -l
```

### Recommended Alerts

- Alert if any IP exceeds 100 per-IP violations per hour
- Alert if global violations spike (> 20 in 5 minutes)
- Alert if 50+ different IPs hit limits simultaneously (DDoS indicator)

## Performance Considerations

- **Cache-backed**: Uses Redis for microsecond-level lookups
- **Atomic operations**: Thread-safe token consumption
- **Minimal memory**: ~1-2KB per active IP
- **Zero blocking**: Failed requests return immediately
- **Compound efficiency**: Both per-IP limiters checked atomically

## Architecture

```
RateLimiter (Event Listener)
    ├─ Per-IP Check (Compound)
    │   ├─ per_ten_minutes (Fixed Window)
    │   └─ per_thirty_seconds (Token Bucket)
    └─ Global Burst Check (Token Bucket)
```

- **Layer**: Integration (Listener)
- **Dependencies**: Symfony HttpKernel, PSR LoggerInterface
- **Per-IP**: Uses client IP as key
- **Global**: Uses fixed key 'global' for single shared bucket

## Disabling Rate Limiting (Development Only)

Comment out the listener in `src/Listener/RateLimiter.php`:

```php
// #[AsEventListener(event: KernelEvents::REQUEST, priority: 50)]
final class RateLimiter { ... }
```

## Files

- `config/packages/rate_limiter.yaml` - Configuration (4 limiters)
- `src/Listener/RateLimiter.php` - Implementation (per-IP + global)
- `config/services.yaml` - Service wiring

## References

- [Symfony Rate Limiter Documentation](https://symfony.com/doc/current/rate_limiter.html)
- [Token Bucket Algorithm](https://en.wikipedia.org/wiki/Token_bucket)
- [Fixed Window Algorithm](https://en.wikipedia.org/wiki/Rate_limiting)
- [HTTP 429 Status Code](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429)
- [Compound Rate Limiting](https://en.wikipedia.org/wiki/Compound_limiter)
