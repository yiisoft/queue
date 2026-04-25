# Performance Tuning

This guide covers techniques for optimizing queue performance and throughput.

## Worker configuration

### Determine optimal worker count

The optimal number of workers depends on the workload type:

**CPU-bound tasks** (image processing, data transformation, encryption):

- Start with: number of workers ≈ number of CPU cores
- Example: 4-core server → 4 workers

**I/O-bound tasks** (API calls, database queries, file operations):

- Can run more workers than CPU cores
- Start with: number of workers ≈ 2-4× number of CPU cores
- Example: 4-core server → 8-16 workers

**Mixed workload**:

- Separate CPU-bound and I/O-bound tasks into different queue names
- Run different worker counts for each queue

**Finding the right number**:

1. Start with the formula above
2. Monitor CPU usage, memory usage, and throughput
3. Gradually increase worker count
4. Stop when throughput plateaus or system resources are saturated

See [Workers](worker.md) for more details on running workers.

For production-ready examples of running multiple workers under `systemd` or Supervisor (including group management, autostart, logs, and reload), see [Running workers in production (systemd and Supervisor)](process-managers.md).

## Memory management

### Configure memory soft limit

Set `memorySoftLimit` to prevent workers from accumulating memory leaks. See [Loops](loops.md) for details on loop configuration:

```php
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Cli\SimpleLoop;

return [
    SignalLoop::class => [
        '__construct()' => [
            'memorySoftLimit' => 256 * 1024 * 1024, // 256MB
        ],
    ],
    SimpleLoop::class => [
        '__construct()' => [
            'memorySoftLimit' => 256 * 1024 * 1024, // 256MB
        ],
    ],
];
```

When a worker reaches the limit:

1. It finishes processing the current message
2. It exits gracefully
3. The process manager restarts it with fresh memory

**Choosing the limit**:

- Monitor actual memory usage of your workers
- Set limit 20-30% above typical usage
- Leave headroom for memory spikes
- Consider your server's total memory

### Prevent memory leaks in message handlers

See [Best practices](best-practices.md) for general message handler design guidelines.

**Clear large objects after use**:

```php
public function handle(MessageInterface $message): void
{
    $largeData = $this->loadLargeDataset($message->getData()['id']);
    
    $this->processData($largeData);
    
    unset($largeData); // Free memory immediately
}
```

**Avoid static caches**:

```php
// Bad - accumulates in memory
class Handler implements MessageHandlerInterface
{
    private static array $cache = [];
    
    public function handle(MessageInterface $message): void
    {
        self::$cache[$message->getData()['id']] = $this->load(...);
        // Cache grows indefinitely
    }
}

// Good - use external cache
class Handler implements MessageHandlerInterface
{
    public function __construct(private CacheInterface $cache) {}
    
    public function handle(MessageInterface $message): void
    {
        $this->cache->set($message->getData()['id'], $this->load(...));
    }
}
```

## Queue name strategy

### Separate workloads by priority

Use different queue names for different priority levels. See [Queue names](queue-names.md) for details on configuring multiple queues:

```php
return [
    'yiisoft/queue' => [
        'queues' => [
            'critical' => AmqpAdapter::class,
            'normal' => AmqpAdapter::class,
            'low' => AmqpAdapter::class,
        ],
    ],
];
```

Run more workers for high-priority queues:

```bash
# 8 workers for critical tasks
systemctl start yii-queue-critical@{1..8}

# 4 workers for normal tasks
systemctl start yii-queue-normal@{1..4}

# 2 workers for low-priority tasks
systemctl start yii-queue-low@{1..2}
```

### Separate by workload type

Create separate queues for different workload characteristics:

```php
return [
    'yiisoft/queue' => [
        'queues' => [
            'fast' => AmqpAdapter::class,      // Quick tasks (< 1s)
            'slow' => AmqpAdapter::class,      // Long tasks (> 10s)
            'cpu-bound' => AmqpAdapter::class, // CPU-intensive
            'io-bound' => AmqpAdapter::class,  // I/O-intensive
        ],
    ],
];
```

Separating queues by workload type prevents slow tasks from blocking fast tasks.

## Middleware optimization

See [Middleware pipelines](middleware-pipelines.md) for details on middleware architecture.

### Minimize middleware overhead

Each middleware adds processing time. Keep the pipeline lean:

```php
return [
    'yiisoft/queue' => [
        'middlewares-consume' => [
            // Only essential middlewares
            MetricsMiddleware::class,
        ],
    ],
];
```

### Avoid heavy operations in middleware

See [Envelopes](envelopes.md) for details on `IdEnvelope`.

**Bad**:

```php
public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest
{
    // Heavy operation on every message
    $this->logger->debug('Full message dump', [
        'message' => json_encode($request->getMessage(), JSON_PRETTY_PRINT),
        'backtrace' => debug_backtrace(),
    ]);
    
    return $handler->handleConsume($request);
}
```

**Good**:

```php
public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest
{
    // Lightweight logging
    $this->logger->debug('Processing message', [
        'id' => $request->getMessage()->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? null,
    ]);
    
    return $handler->handleConsume($request);
}
```

## Message design for performance

### Batch related operations

Instead of sending many small messages, batch them when possible:

**Bad** (1000 messages):

```php
foreach ($userIds as $userId) {
    $queue->push(new Message(SendEmailHandler::class, [
        'userId' => $userId,
    ]));
}
```

**Good** (1 message):

```php
$queue->push(new Message(SendBulkEmailHandler::class, [
    'userIds' => $userIds, // Process in batches
]));
```

**In the handler**:

```php
public function handle(MessageInterface $message): void
{
    $userIds = $message->getData()['userIds'];
    
    // Process in chunks to avoid memory issues
    foreach (array_chunk($userIds, 100) as $chunk) {
        $this->emailService->sendBulk($chunk);
    }
}
```

### Avoid deep envelope stacking

While [envelope](envelopes.md) stacking is optimized, deep nesting still has overhead:

```php
// Avoid excessive wrapping
$message = new Message(...);
$message = new Envelope1($message);
$message = new Envelope2($message);
$message = new Envelope3($message);
$message = new Envelope4($message);
$message = new Envelope5($message); // Too many layers
```

Keep envelope depth reasonable (typically 2-3 layers).

## Database optimization

### Use connection pooling

For database-heavy message handlers, use an external connection pooler such as [PgBouncer](https://www.pgbouncer.org/) (PostgreSQL) or [ProxySQL](https://proxysql.com/) (MySQL/MariaDB). These sit between your workers and the database server, maintaining a pool of live connections and reusing them across requests — reducing connection overhead without requiring any application-level changes.

### Batch database operations

Combine multiple operations into fewer queries:

**Bad**:

```php
public function handle(MessageInterface $message): void
{
    foreach ($message->getData()['items'] as $item) {
        $this->db->insert('items', $item); // N queries
    }
}
```

**Good**:

```php
public function handle(MessageInterface $message): void
{
    $this->db->batchInsert('items', $message->getData()['items']); // 1 query
}
```

### Use read replicas for read-heavy handlers

If your message handler only reads data, use read replicas:

```php
final class GenerateReportHandler implements MessageHandlerInterface
{
    public function __construct(
        private ConnectionInterface $readDb, // Read replica
        private ReportGenerator $generator,
    ) {}
    
    public function handle(MessageInterface $message): void
    {
        $data = $this->readDb->query('SELECT ...'); // From replica
        $this->generator->generate($data);
    }
}
```

## Monitoring and profiling

See [Yii Debug integration](debug-integration.md) for built-in debugging tools.

### Track key metrics

Monitor these metrics to identify bottlenecks:

**Queue metrics**:

- Queue depth (messages waiting)
- Processing rate (messages/second)
- Average processing time
- Failure rate (see [Error handling](error-handling.md))

**Worker metrics**:

- CPU usage per worker
- Memory usage per worker
- Number of active workers

**System metrics**:

- Overall CPU usage
- Overall memory usage
- Network I/O
- Disk I/O

### Use profiling for slow handlers

Profile slow message handlers to find bottlenecks:

```php
public function handle(MessageInterface $message): void
{
    $profiler = new Profiler();
    
    $profiler->start('database');
    $data = $this->loadData($message->getData()['id']);
    $profiler->stop('database');
    
    $profiler->start('processing');
    $result = $this->processData($data);
    $profiler->stop('processing');
    
    $profiler->start('storage');
    $this->saveResult($result);
    $profiler->stop('storage');
    
    $this->logger->debug('Handler profile', $profiler->getResults());
}
```

## Testing performance

### Benchmark under realistic load

Test with realistic message volumes and data:

```php
// Load test script
$queue = $container->get(QueueInterface::class);

$start = microtime(true);
$count = 10000;

for ($i = 0; $i < $count; $i++) {
    $queue->push(new Message(TestHandler::class, [
        'id' => $i,
        'data' => $this->generateRealisticData(),
    ]));
}

$duration = microtime(true) - $start;
echo "Pushed $count messages in $duration seconds\n";
echo "Rate: " . ($count / $duration) . " messages/second\n";
```

### Monitor during load tests

Run load tests while monitoring:

- Worker CPU and memory usage
- Queue depth growth
- Processing latency
- Error rates

Adjust configuration based on observations.

## Common performance issues

### Issue: Queue depth keeps growing

**Symptoms**: Messages accumulate faster than they're processed.

**Solutions**:

1. Add more workers (see [Workers](worker.md))
2. Optimize slow message handlers
3. Increase prefetch count (if using AMQP)
4. Separate slow and fast tasks into different queues (see [Queue names](queue-names.md))

### Issue: High memory usage

**Symptoms**: Workers consume excessive memory.

**Solutions**:

1. Lower `memorySoftLimit` (set a smaller numeric value) to restart workers more frequently by hitting the memory threshold sooner (see [Loops](loops.md))
2. Fix memory leaks in message handlers (see [Best practices](best-practices.md))
3. Reduce prefetch count
4. Process large datasets in chunks

### Issue: Low throughput despite available resources

**Symptoms**: CPU/memory underutilized, but throughput is low.

**Solutions**:

1. Increase worker count (see [Workers](worker.md))
2. Increase prefetch count
3. Reduce middleware overhead (see [Middleware pipelines](middleware-pipelines.md))
4. Check for network bottlenecks
5. Optimize database queries in handlers

### Issue: Uneven load distribution

**Symptoms**: Some workers are busy while others are idle.

**Solutions**:

1. Lower prefetch count for better distribution
2. Use shorter message processing times
3. Check broker configuration (e.g., RabbitMQ queue settings)
