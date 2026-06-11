# Rate Limiting Implementation

This document explains the dual-tier rate limiting system implemented for the Momentum backend.

## Overview

The application uses Symfony's built-in Rate Limiter component with **two independent rate limiters**:

1. **Per-IP Rate Limiter**: 30 requests per 30 seconds per individual IP address
2. **Global Bucket Rate Limiter**: 100 requests per 1 minute globally (all IPs combined)

This two-tier approach provides optimal protection:
- **Per-IP**: Prevents single bad actors from overwhelming the server
- **Global**: Prevents total server overload even from distributed sources

## Configuration

### Rate Limiter Configuration (`config/packages/rate_limiter.yaml`)

```yaml
framework:
    rate_limiter:
        global_limiter:
            policy: 'token_bucket'
            limit: 30
            interval: '30 seconds'
        
        global_bucket:
            policy: 'token_bucket'
            limit: 100
            interval: '1 minute'
```

**Parameters:**
- **policy**: `token_bucket` - Uses token bucket algorithm for fair rate limiting
- **limit**: Number of requests allowed per interval
- **interval**: Time period for refilling tokens
- **cache_pool**: Uses Redis (via cache system) to track limiter state

## How It Works

### Request Flow

1. **Request arrives** → RateLimitListener intercepts at priority 50
2. **Per-IP check**: Extracts client IP and consumes 1 token from per-IP limiter
   - ✅ Token available → Continue
   - ❌ No tokens → Return 429 with `Retry-After: 30`
3. **Global bucket check**: Consumes 1 token from global limiter (using fixed key 'global')
   - ✅ Token available → Request proceeds normally
   - ❌ No tokens → Return 429 with `Retry-After: 60`

### Example Scenarios

**Scenario A: Single IP hitting limits**
```
IP 192.168.1.1: Makes 31 requests in 30 seconds
→ First 30 succeed
→ Request 31: Blocked by per-IP limiter (429)
→ Request 32: Blocked by per-IP limiter (429)

IP 192.168.1.2: Makes 1 request
→ Request succeeds (per-IP bucket is independent)
```

**Scenario B: Multiple IPs hitting global limit**
```
IP 192.168.1.1: Makes 50 requests
IP 192.168.1.2: Makes 50 requests
Total: 100 requests in 1 minute
→ All succeed (distributed across IPs)

IP 192.168.1.3: Makes 1 request
→ Blocked by global bucket limiter (429)
→ Must wait up to 1 minute for bucket to refill
```

## Logging

Rate limit violations are logged with detailed context:

```php
$this->logger->warning('Per-IP rate limit exceeded', [
    'client_ip' => '192.168.1.1',
    'method' => 'GET',
    'path' => '/activities',
    'retry_after_timestamp' => 1718118900,
]);

$this->logger->warning('Global bucket rate limit exceeded', [
    'client_ip' => '192.168.1.5',
    'method' => 'POST',
    'path' => '/api/sync',
    'retry_after_timestamp' => 1718118960,
]);
```

### Viewing Logs

**Development:**
```bash
docker compose logs -f app | grep "rate limit"
```

**Production:** Check your logging system (ELK Stack, CloudWatch, Datadog, etc) for:
- Level: `warning`
- Messages: "Per-IP rate limit exceeded" or "Global bucket rate limit exceeded"

## Response Behavior

### Per-IP Limit Exceeded
```
HTTP/1.1 429 Too Many Requests
Retry-After: 30
Content-Type: text/plain

Too Many Requests
```

### Global Bucket Limit Exceeded
```
HTTP/1.1 429 Too Many Requests
Retry-After: 60
Content-Type: text/plain

Too Many Requests
```

## Storage

Rate limit data is stored in the application's cache system (Redis in production):
- **Per-IP buckets**: One bucket per unique IP address
- **Global bucket**: Single shared bucket with fixed key 'global'
- Both refill independently according to their configured intervals

## Customization

### Change Rate Limits

Edit `config/packages/rate_limiter.yaml`:

```yaml
global_limiter:
    limit: 60                    # Increase per-IP to 60 requests
    interval: '1 minute'         # Change interval to 1 minute

global_bucket:
    limit: 500                   # Increase global to 500 requests
    interval: '1 minute'         # Keep at 1 minute interval
```

### Add Per-Endpoint Rate Limiting

Create additional limiters for specific endpoints:

```yaml
framework:
    rate_limiter:
        global_limiter: { ... }
        global_bucket: { ... }
        
        auth_limiter:
            policy: 'token_bucket'
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
            $limiter = $this->rateLimiterAuth->create($clientIp);
            // ... check limits ...
        }
    }
}
```

### Per-User Rate Limiting

For authenticated users, use user ID instead of IP:

```php
if ($user = $this->getUser()) {
    $key = 'user_' . $user->getId();
} else {
    $key = $clientIp;
}
$limiter = $this->rateLimiter->create($key);
```

## Testing

### Manual Testing

Test per-IP limiting:
```bash
# Send 31 requests rapidly
for i in {1..31}; do
  curl -s http://localhost:8080/activities -w "%{http_code}\n"
done

# Expected: 30 × 200, 1 × 429
```

Test global bucket limiting:
```bash
# Terminal 1 (IP 1)
for i in {1..50}; do curl -s http://localhost:8080/ -w "%{http_code}\n"; done

# Terminal 2 (IP 2)  
for i in {1..50}; do curl -s http://localhost:8080/ -w "%{http_code}\n"; done

# Terminal 3 (IP 3)
curl -s http://localhost:8080/ -w "%{http_code}\n"  # Should get 429 or 200 depending on timing
```

### View Violations

```bash
docker compose logs app | grep "rate limit exceeded"
```

## Monitoring & Metrics

### Track Violations

Extract logs to identify patterns:

```bash
# Count violations per IP
docker compose logs app | grep "Per-IP rate limit exceeded" | grep -oP '(?<="client_ip": ")[^"]+' | sort | uniq -c

# Count violations per endpoint
docker compose logs app | grep "rate limit exceeded" | grep -oP '(?<="path": ")[^"]+' | sort | uniq -c
```

### Recommended Alerts

- Alert if any single IP exceeds 100 violations per hour
- Alert if global bucket violations exceed 10 per minute
- Alert if violations spike unexpectedly (DDoS detection)

## Performance Considerations

- **Cache-backed**: Uses Redis/APCu for microsecond lookups
- **Independent buckets**: Each IP has isolated state
- **Atomic operations**: Token consumption is thread-safe
- **Memory efficient**: Minimal storage per IP (typically <1KB per active IP)
- **Zero blocking**: Failed requests respond immediately without computation

## Disabling Rate Limiting (Development Only)

To disable rate limiting during development:

**Option 1: Comment out listener**
```php
// In src/Listener/RateLimitListener.php
// #[AsEventListener(event: KernelEvents::REQUEST, priority: 50)]
final class RateLimitListener { ... }
```

**Option 2: Environment variable**
```bash
# In .env.local
DISABLE_RATE_LIMIT=1
```

Then check in listener:
```php
if ($_ENV['DISABLE_RATE_LIMIT'] ?? false) {
    return;
}
```

## Architecture Integration

- **Generic Layer**: Uses Symfony HttpKernel components
- **Integration Layer**: Listener in `src/Listener/RateLimitListener.php`
- **No core logic impact**: Business logic unaffected
- **Layered design compliant**: Respects Deptrac architecture rules

## Files

- `config/packages/rate_limiter.yaml` - Configuration
- `src/Listener/RateLimitListener.php` - Implementation
- `config/services.yaml` - Service wiring

## References

- [Symfony Rate Limiter Documentation](https://symfony.com/doc/current/rate_limiter.html)
- [Token Bucket Algorithm](https://en.wikipedia.org/wiki/Token_bucket)
- [HTTP 429 Status Code](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429)
- [Distributed Rate Limiting](https://en.wikipedia.org/wiki/Rate_limiting)
