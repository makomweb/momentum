# Async Task Processing with Symfony Messenger

Momentum uses **Symfony Messenger** with a **database transport** to enable async task processing. This guide explains how to implement and dispatch async tasks.

## Architecture

```
HTTP Request Handler          Background Worker Process
    (Main App)                      (Worker Service)
        ↓                                  ↓
   Dispatch Message       ←→ messenger_messages (DB)
        ↓                                  ↓
   Message Queued              Message Handler
        ↓                                  ↓
   Returns to User            Process Async Task
```

## Creating a New Async Task

### 1. Create a Message Class

Messages are **immutable data objects** that represent an async task.

Create a new file: `src/Async/Message/YourTaskMessage.php`

```php
<?php
declare(strict_types=1);

namespace App\Async\Message;

final readonly class SyncActivitiesMessage
{
    public function __construct(
        public int $userId,
        public string $accessToken,
    ) {
    }
}
```

**Rules:**
- Use `final readonly` for immutability
- Constructor parameters become public properties (automatically)
- Keep messages lightweight (only pass IDs/references, not objects)
- Include proper documentation

### 2. Create a Message Handler

Handlers process messages. They're automatically discovered via the `#[AsMessageHandler]` attribute.

Create a new file: `src/Async/Handler/SyncActivitiesHandler.php`

```php
<?php
declare(strict_types=1);

namespace App\Async\Handler;

use App\Async\Message\SyncActivitiesMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncActivitiesHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private StravaClient $stravaClient,
    ) {
    }

    public function __invoke(SyncActivitiesMessage $message): void
    {
        $this->logger->info('Syncing activities', ['userId' => $message->userId]);
        
        // Perform the actual async work
        $activities = $this->stravaClient->getActivities($message->accessToken);
        
        // Save to database, etc...
        $this->logger->info('Activities synced', ['count' => count($activities)]);
    }
}
```

**Rules:**
- Use `#[AsMessageHandler]` attribute
- Constructor can inject any service (auto-wired)
- Use `__invoke(MessageClass $message): void` as handler method
- Log progress and errors

## Dispatching Messages

From any controller, command, or service that can inject `MessageBusInterface`:

```php
<?php
use Symfony\Component\Messenger\MessageBusInterface;
use App\Async\Message\SyncActivitiesMessage;

class ActivityController
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function sync(): Response
    {
        // Dispatch message to queue
        $this->bus->dispatch(
            new SyncActivitiesMessage(
                userId: $this->getUser()->getId(),
                accessToken: $this->getUser()->getStravaToken(),
            )
        );

        // Return immediately (message will be processed in background)
        return $this->render('sync_scheduled.html.twig');
    }
}
```

## Configuration

### Transports

The `messenger.yaml` defines how messages are routed:

```yaml
framework:
    messenger:
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'    # Database queue
            sync: 'sync://'                             # Synchronous (for testing)
            
        routing:
            'App\Async\Message\*': async              # Route all messages to async
```

### Database Transport

Messages are stored in the `messenger_messages` table:

```
mysql> SELECT * FROM messenger_messages;
+----+-------+----------+-----------+-----------+------------+-----------+
| id | body  | headers  | queue_name| created_at| available_at| delivered_at|
+----+-------+----------+-----------+-----------+------------+-----------+
```

The database transport is:
- **Simple**: No additional infrastructure (Redis, RabbitMQ) needed
- **Durable**: Messages survive container restarts
- **Sufficient**: For most applications (except extreme throughput)

### Environment Variable

Set in `.env.dev` or `.env`:

```
MESSENGER_TRANSPORT_DSN=doctrine://default?table_name=messenger_messages&queue_name=default
```

- `table_name=messenger_messages` - Where messages are stored
- `queue_name=default` - Queue name (can have multiple queues)

## Worker Service

The background worker processes queued messages. It runs continuously in Docker:

```bash
# In docker-compose.yaml:
worker:
    build:
        context: .
        dockerfile: build/Dockerfile.worker
    command: php bin/console messenger:consume async --time-limit=3600 --memory-limit=512M
    depends_on:
        db:
            condition: service_healthy
    volumes:
        - ./app:/app
```

**Options:**
- `--time-limit=3600` - Worker exits after 1 hour (container restarts it)
- `--memory-limit=512M` - Exit if memory exceeds 512MB
- `-vv` - Verbose output (add to logs)

### Managing the Worker

```bash
# Start/restart worker
docker compose restart worker

# View logs
docker compose logs -f worker

# Stop worker gracefully (waits for current message)
docker compose stop worker

# Stop immediately
docker compose kill worker
```

## Testing

### Running Tests in Test Environment

Tests use the `in-memory://` transport (no database needed):

```php
<?php
namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Async\Message\SyncActivitiesMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class AsyncTaskTest extends KernelTestCase
{
    public function test_message_dispatched_to_queue(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $bus = $container->get(MessageBusInterface::class);
        $transport = $container->get('messenger.transport.async');

        $message = new SyncActivitiesMessage(userId: 123, accessToken: 'token');
        $bus->dispatch($message);

        // Verify message was queued
        $this->assertCount(1, $transport->get());
    }
}
```

### Manual Testing

```bash
# Check queue status
docker compose exec app php bin/console messenger:stats

# Dispatch test message and watch worker
# 1. In one terminal:
docker compose logs -f worker

# 2. In another, dispatch:
docker compose exec app php bin/console app:your-command-that-dispatches

# Watch worker consume it
```

## Monitoring & Troubleshooting

### Queue Status

```bash
docker compose exec app php bin/console messenger:stats
```

Output:
```
----------- -------
  Transport  Count
----------- -------
  async      5
  failed     0
----------- -------
```

### Failed Messages

Messages that throw exceptions are moved to the `failed` transport:

```bash
# View failed messages
docker compose exec app php bin/console messenger:failed:show

# Retry a failed message
docker compose exec app php bin/console messenger:failed:retry <id>

# Remove failed message
docker compose exec app php bin/console messenger:failed:remove <id>
```

### Common Issues

**Worker not consuming messages:**
- Check worker is running: `docker compose ps worker`
- Check logs: `docker compose logs worker`
- Verify environment variable: `docker compose exec app php -r 'echo getenv("MESSENGER_TRANSPORT_DSN");'`

**Messages stuck in queue:**
- Check worker memory: `docker compose logs worker | grep memory`
- Check for exceptions in handler: Add try-catch and log
- Restart worker: `docker compose restart worker`

**Handler not found:**
- Verify `#[AsMessageHandler]` attribute is present
- Check class is in `src/Async/Handler/`
- Clear cache: `docker compose exec app php bin/console cache:clear`

## Best Practices

1. **Keep Messages Small**
   - Pass user/entity IDs, not full objects
   - Let handlers fetch fresh data from database

2. **Make Handlers Idempotent**
   - Handlers may be retried after failure
   - Same message processed twice should be safe

3. **Handle Exceptions**
   - Throw exceptions to retry (with exponential backoff)
   - Log errors with context for debugging

4. **Monitor Queue Size**
   - Run `messenger:stats` regularly
   - Growing queue = handlers too slow

5. **Use Meaningful Names**
   - Message: `SyncActivitiesMessage` (what to do)
   - Handler: `SyncActivitiesHandler` (who does it)

## Examples

See:
- `src/Async/Message/ExampleMessage.php` - Message template
- `src/Async/Handler/ExampleMessageHandler.php` - Handler template
- `config/packages/messenger.yaml` - Messenger configuration
