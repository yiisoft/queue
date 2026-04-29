# Manual Configuration (without [yiisoft/config](https://github.com/yiisoft/config))

This guide explains how to set up the queue component manually, without using [yiisoft/config](https://github.com/yiisoft/config).

## Basic setup

To use the queue, you need to create instances of the following classes:

1. **Adapter** - handles the actual queue backend like AMQP, Redis, etc.
2. **Worker** - processes messages from the queue
3. **Queue** - the main entry point for pushing messages

### Example

```php
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\MiddlewareFactoryConsume;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFactoryFailure;
use Yiisoft\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\Worker\Worker;

// A PSR-11 container is required for resolving dependencies of middleware and handlers.
// How you build it is up to you.
/** @var ContainerInterface $container */

$logger = new NullLogger(); // replace with your PSR-3 logger in production

// Define message handlers
$handlers = [
    'file-download' => [FileDownloader::class, 'handle'],
    FileDownloader::class => [FileDownloader::class, 'handle'],
];

$callableFactory = new CallableFactory($container);

// Create middleware dispatchers
$consumeMiddlewareDispatcher = new ConsumeMiddlewareDispatcher(
    new MiddlewareFactoryConsume($container, $callableFactory),
);

$failureMiddlewareDispatcher = new FailureMiddlewareDispatcher(
    new MiddlewareFactoryFailure($container, $callableFactory),
    [],
);

$pushMiddlewareDispatcher = new PushMiddlewareDispatcher(
    new MiddlewareFactoryPush($container, $callableFactory),
);

// Create worker
$worker = new Worker(
    $handlers,
    $logger,
    new Injector($container),
    $container,
    $consumeMiddlewareDispatcher,
    $failureMiddlewareDispatcher,
    $callableFactory,
);

// Create loop (SignalLoop requires ext-pcntl; SimpleLoop works without it)
$loop = new SimpleLoop();

// Create queue. Without an adapter the queue runs in synchronous mode (messages are processed
// immediately on push). Pass an adapter (e.g., AMQP, Redis) for asynchronous processing.
$queue = new Queue(
    $worker,
    $loop,
    $logger,
    $pushMiddlewareDispatcher,
);

// Now you can push messages
$message = new \Yiisoft\Queue\Message\Message('file-download', ['url' => 'https://example.com/file.pdf']);
$queue->push($message);
```

## Using Queue Provider

For multiple queue names, use `PredefinedQueueProvider` (maps queue names to pre-built queue instances):

```php
use Yiisoft\Queue\Provider\PredefinedQueueProvider;

// PredefinedQueueProvider: pass fully built queue instances.
$provider = new PredefinedQueueProvider([
    'queue1' => $queue1,
    'queue2' => $queue2,
]);
```

## Running the queue

### Processing existing messages

```php
$queue->run();      // Process all messages
$queue->run(10);    // Process up to 10 messages
```

### Listening for new messages

```php
$queue->listen();   // Run indefinitely
```

## Next steps

- [Usage basics](usage.md) - learn how to create messages and handlers
- [Message handler](message-handler.md) - understand handler formats
- [Error handling](error-handling.md) - configure retries and failure handling
- [Adapter list](adapter-list.md) - choose a production-ready adapter
