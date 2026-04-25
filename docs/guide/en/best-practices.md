# Best Practices

This guide covers recommended practices for building reliable and maintainable queue-based applications.

## Message handler design

### Make message handlers idempotent

#### Bad

```php
final class ProcessPaymentHandler implements MessageHandlerInterface
{
    public function handle(MessageInterface $message): void
    {
        $paymentId = $message->getData()['paymentId'];
        
        // Always processes payment, even if already done
        $this->paymentService->process($paymentId);
    }
}
```

#### Good

```php
final class ProcessPaymentHandler implements MessageHandlerInterface
{
    public function handle(MessageInterface $message): void
    {
        $paymentId = $message->getData()['paymentId'];
        
        // Check if already processed
        if ($this->paymentRepository->isProcessed($paymentId)) {
            return; // Skip duplicate
        }
        
        // Process payment in a transaction
        $this->db->transaction(function () use ($paymentId) {
            $this->paymentService->process($paymentId);
            $this->paymentRepository->markAsProcessed($paymentId);
        });
    }
}
```

#### Why

- Network failures may cause message redelivery.
- [Failure handling middleware](error-handling.md) may retry failed messages.
- Some adapters use at-least-once delivery semantics.
- Processing the same message multiple times should produce the same result as processing it once.

### Keep message handlers stateless

Avoid storing per-message state in handler properties. The container may return the same handler instance for multiple consecutive messages, so handlers should not store state between invocations. Queue workers are often long-running processes, which amplifies this issue.

#### Bad

```php
final class ProcessPaymentHandler implements MessageHandlerInterface
{
    private array $processedIds = [];

    public function handle(MessageInterface $message): void
    {
        $paymentId = $message->getData()['paymentId'];

        // State leaks between messages and grows over time
        if (isset($this->processedIds[$paymentId])) {
            return;
        }

        $this->paymentService->process($paymentId);
        $this->processedIds[$paymentId] = true;
    }
}
```

#### Good

```php
final class ProcessPaymentHandler implements MessageHandlerInterface
{
    public function handle(MessageInterface $message): void
    {
        $paymentId = $message->getData()['paymentId'];

        // Use persistent storage for deduplication/idempotency
        if ($this->paymentRepository->isProcessed($paymentId)) {
            return;
        }

        $this->db->transaction(function () use ($paymentId) {
            $this->paymentService->process($paymentId);
            $this->paymentRepository->markAsProcessed($paymentId);
        });
    }
}
```

#### Why

- Stateful handlers can produce unpredictable behavior when the same instance handles multiple messages.
- Long-running workers amplify memory leaks and stale state issues.
- Stateless handlers are easier to test and reason about.

### Handle exceptions appropriately

#### Bad

```php
public function handle(MessageInterface $message): void
{
    try {
        $this->service->process($message->getData());
    } catch (\Throwable $e) {
        // Message is marked as processed but actually failed
    }
}
```

#### Good

```php
public function handle(MessageInterface $message): void
{
    $this->service->process($message->getData());
    // Exception will trigger failure handling
}
```

#### Why

- Exceptions trigger [failure handling middleware](error-handling.md) which can retry or redirect messages.
- Catching and suppressing exceptions marks the message as successfully processed when it actually failed.
- Let exceptions bubble up unless you have a specific reason to handle them.

## Message design

### Keep messages small

Messages should contain only the data needed for processing. Avoid embedding large payloads.

#### Bad

```php
new Message(ProcessImageHandler::class, [
    'imageData' => base64_encode($imageContent), // Large binary data
    'operations' => ['resize', 'watermark'],
]);
```

#### Good

```php
new Message(ProcessImageHandler::class, [
    'imageId' => 12345, // You can get the image itself from DB or disk by id in a message handler
    'operations' => ['resize', 'watermark'],
]);
```

#### Why

- Message brokers have size limits (e.g., RabbitMQ default is 128MB).
- Large messages increase network overhead and serialization cost.
- Storing data in the database and passing IDs is more efficient.

### Ensure data is serializable

#### Bad

```php
new Message(SendEmailHandler::class, [
    'to' => 'user@example.com',
    'mailer' => $this->mailer, // Object instance
    'callback' => fn() => $this->log(), // Closure
]);
```

#### Good

```php
new Message(SendEmailHandler::class, [
    'to' => 'user@example.com',
    'subject' => 'Welcome',
    'templateId' => 'welcome-email',
]);
```

#### Why

- Message data must be JSON-serializable when using the default `JsonMessageSerializer`.
- Resources (file handles, database connections, sockets) cannot be serialized.
- Closures and anonymous functions cannot be serialized.
- Objects with circular references or without proper serialization support will fail.

## Message type

### Use stable types (not FQCN) for inter-service communication

#### Bad

```php
// External system pushes messages with handler class name
new Message('\App\Queue\EmailHandler', ['to' => 'user@example.com']);
```

#### Good

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'send-email' => [EmailHandler::class, 'handle'],
            'process-payment' => [PaymentHandler::class, 'handle'],
        ],
    ],
];

// External system uses stable type
new Message('send-email', ['to' => 'user@example.com']);
```

#### Why

- Short stable types decouple producer and consumer implementations.
- External systems don't need to know your internal PHP class names.
- You can refactor handler classes without breaking external producers.

### Use FQCN for internal tasks

#### Bad

```php
// Requires configuration for internal tasks
return [
    'yiisoft/queue' => [
        'handlers' => [
            'generate-report' => [GenerateReportHandler::class, 'handle'],
        ],
    ],
];

$queue->push(new Message('generate-report', ['reportId' => 123]));
```

#### Good

```php
// No configuration needed
$queue->push(new Message(
    GenerateReportHandler::class,
    ['reportId' => 123]
));
```

#### Why

- Using the FQCN as the message type is simpler for internal tasks.
- This approach is refactoring-safe (IDE can rename the class).
- Requires no configuration mapping.

#### More info

See [Message handler](message-handler.md) for details.

## Monitoring and observability

### Use middleware for metrics collection

#### Bad

```php
// Metrics collection in every handler
final class EmailHandler implements MessageHandlerInterface
{
    public function handle(MessageInterface $message): void
    {
        $start = microtime(true);
        $this->sendEmail($message->getData());
        $this->metrics->timing('email.duration', microtime(true) - $start);
    }
}
```

#### Good

```php
final class MetricsMiddleware implements MiddlewareConsumeInterface
{
    public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest
    {
        $start = microtime(true);
        
        try {
            $result = $handler->handleConsume($request);
            $this->metrics->increment('queue.processed');
            return $result;
        } catch (\Throwable $e) {
            $this->metrics->increment('queue.failed');
            throw $e;
        } finally {
            $duration = microtime(true) - $start;
            $this->metrics->timing('queue.duration', $duration);
        }
    }
}
```

#### Why

- [Middleware](middleware-pipelines.md) centralizes metrics collection in one place.
- Handlers stay focused on business logic.
- Consistent metrics across all message types.
- Easy to add/remove metrics without changing handlers.

### Log message IDs for tracing

#### Bad

```php
$queue->push($message);
$this->logger->info('Queued task');
```

#### Good

```php
$pushedMessage = $queue->push($message);
$id = $pushedMessage->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? null;

$this->logger->info('Queued task', [
    'messageId' => $id,
    'messageType' => $message->getType(),
]);
```

#### Why

- Message IDs correlate logs across producer and consumer.
- Makes debugging easier when tracking message flow.
- Helps identify which specific message failed.

#### More info

See [Envelopes](envelopes.md) for details on `IdEnvelope`.

### Set up alerts for failed messages

#### Bad

No monitoring, failures go unnoticed

#### Good

Monitor and alert on:
- Failure rate > 5%
- Queue depth > 1000 messages (monitor via broker API or tools)
- Set up alerts when thresholds are exceeded

#### Why

- Alert on high failure rates to catch issues early.
- Monitor queue depth to detect processing bottlenecks.
- Proactive monitoring prevents data loss and service degradation.

## Production deployment

### Use SignalLoop for graceful shutdown

#### Bad

```php
// Using SimpleLoop without signal handling
use Yiisoft\Queue\Cli\SimpleLoop;

return [
    LoopInterface::class => SimpleLoop::class,
];
```

#### Good

```php
use Yiisoft\Queue\Cli\SignalLoop;

return [
    SignalLoop::class => [
        '__construct()' => [
            'memorySoftLimit' => 256 * 1024 * 1024, // 256MB
        ],
    ],
];
```

#### Why

- Allows workers to finish processing the current message before shutting down on `SIGTERM`/`SIGINT`.
- Prevents message loss during deployment or shutdown.

#### More info

- Ensure `ext-pcntl` is installed and `SignalLoop` is used.
- See [Loops](loops.md) for details.

### Use a process manager
 
#### Bad
 
 ```bash
 # Running worker manually without supervision in production
 php yii queue:listen
 ```
 
#### Good

Run workers under a process manager such as `systemd` or Supervisor.

#### Why

- Process managers ensure workers restart automatically on failure.
- Workers start automatically on server boot.
- Easier to manage multiple worker instances.

#### More info

See [Running workers in production (systemd and Supervisor)](process-managers.md).

### Configure memory limits

#### Bad

```php
// No memory limit - workers accumulate memory leaks
use Yiisoft\Queue\Cli\SignalLoop;

return [
    SignalLoop::class => [
        '__construct()' => [
            'memorySoftLimit' => 0, // No limit
        ],
    ],
];
```

#### Good

```php
use Yiisoft\Queue\Cli\SignalLoop;

return [
    SignalLoop::class => [
        '__construct()' => [
            'memorySoftLimit' => 200 * 1024 * 1024, // 200MB, lower than a hard limit of 256MB
        ],
    ],
];
```

#### Why

- Prevents memory leaks from accumulating over time.
- When the limit is reached, the worker finishes the current message and exits.
- The process manager automatically restarts it with fresh memory.

#### More info

See [Loops](loops.md) and [Performance tuning](performance-tuning.md) for more details.

## Testing

### Test message handlers in isolation

#### Bad

```php
// Testing through the queue (integration test)
public function testProcessesPayment(): void
{
    $queue->push(new Message(ProcessPaymentHandler::class, ['paymentId' => 123]));
    // Hard to verify behavior, slow, requires queue setup
}
```

#### Good

```php
final class ProcessPaymentHandlerTest extends TestCase
{
    public function testProcessesPayment(): void
    {
        $handler = new ProcessPaymentHandler(
            $this->createMock(PaymentService::class),
            $this->createMock(PaymentRepository::class),
        );
        
        $message = new Message(ProcessPaymentHandler::class, [
            'paymentId' => 123,
        ]);
        
        $handler->handle($message);
        
        // Assert expected behavior
    }
}
```

#### Why

- Message handlers are regular classes and can be unit-tested.
- Unit tests are faster and more focused than integration tests.
- Easy to mock dependencies and verify behavior.
- No queue infrastructure needed for testing.

## Security

### Validate message data

#### Bad

```php
public function handle(MessageInterface $message): void
{
    $data = $message->getData();
    
    // No validation - trusts all input
    $this->processUser($data['userId']);
}
```

#### Good

```php
public function handle(MessageInterface $message): void
{
    $data = $message->getData();
    
    if (!isset($data['userId']) || !is_int($data['userId'])) {
        throw new InvalidArgumentException('Invalid userId');
    }
    
    $this->processUser($data['userId']);
}
```

#### Why

- Message data can come from untrusted sources.
- Validation prevents type errors and security issues.
- Fails fast with clear error messages.
- Protects against malformed or malicious data.

### Don't trust external producers

#### Bad

```php
public function handle(MessageInterface $message): void
{
    $data = $message->getData();
    
    // Directly using external data in SQL
    $this->db->query("DELETE FROM users WHERE id = {$data['userId']}");
}
```

#### Good

```php
public function handle(MessageInterface $message): void
{
    $data = $message->getData();
    
    // Validate and sanitize
    if (!isset($data['userId']) || !is_int($data['userId']) || $data['userId'] <= 0) {
        throw new InvalidArgumentException('Invalid userId');
    }
    
    // Use parameterized query
    $this->db->query('DELETE FROM users WHERE id = :id', ['id' => $data['userId']]);
}
```

#### Why

- When consuming messages from external systems, treat all data as untrusted.
- Validate all fields before processing.
- Sanitize inputs before using in queries or commands.
- Use allowlists instead of denylists.
- Prevents SQL injection and other security vulnerabilities.

#### More info

See [Consuming messages from external systems](consuming-messages-from-external-systems.md).

### Avoid sensitive data in messages

#### Bad

```php
new Message(SendEmailHandler::class, [
    'userEmail' => 'user@example.com',
    'apiKey' => 'secret-key-here', // Secret in message
    'password' => 'user-password', // Sensitive data
]);
```

#### Good

```php
new Message(SendEmailHandler::class, [
    'userId' => 123,
    'templateId' => 'welcome',
]);
```

#### Why

- Message payloads may be logged, stored, or transmitted insecurely.
- Passwords, API keys, and secrets should never be in messages.
- Use references (IDs) instead of sensitive data.
- Retrieve sensitive data from secure storage in the handler.
