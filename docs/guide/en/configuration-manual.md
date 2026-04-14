# Manual Configuration (without [yiisoft/config](https://github.com/yiisoft/config))

This guide explains how to set up the queue component manually, without using [yiisoft/config](https://github.com/yiisoft/config).

## Basic setup

To use the queue, you need to create instances of the following classes:

1. **Adapter** - handles the actual queue backend (e.g., `SynchronousAdapter`, or an adapter from external packages like Redis, AMQP, etc.)
2. **Worker** - processes messages from the queue
3. **Queue** - the main entry point for pushing messages

### Example

```php
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Adapter\SynchronousAdapter;
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
    new MiddlewareFactoryPush($container),
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

// Create queue (adapter is wired in a second step due to mutual dependency)
$queue = new Queue(
    $worker,
    $loop,
    $logger,
    $pushMiddlewareDispatcher,
);

// SynchronousAdapter needs a queue reference — create it after the queue
$adapter = new SynchronousAdapter($worker, $queue);

// Attach the adapter to the queue (returns a new Queue instance)
$queue = $queue->withAdapter($adapter);

// Now you can push messages
$message = new \Yiisoft\Queue\Message\Message('file-download', ['url' => 'https://example.com/file.pdf']);
$queue->push($message);
```

## Using Queue Provider

For multiple queue names, use `AdapterFactoryQueueProvider` (maps queue names to adapter definitions) or `PredefinedQueueProvider` (maps queue names to pre-built queue instances):

```php
use Yiisoft\Queue\Provider\AdapterFactoryQueueProvider;
use Yiisoft\Queue\Adapter\SynchronousAdapter;

// AdapterFactoryQueueProvider: each queue name maps to an adapter definition.
// The provider wraps each adapter in a Queue with the given name.
$definitions = [
    'queue1' => new SynchronousAdapter($worker, $queue),
    'queue2' => SynchronousAdapter::class,
];

$provider = new AdapterFactoryQueueProvider(
    $queue,
    $definitions,
    $container,
);

$queueForQueue1 = $provider->get('queue1');
$queueForQueue2 = $provider->get('queue2');
```

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
- [Message handler](message-handler-simple.md) - understand handler formats
- [Error handling](error-handling.md) - configure retries and failure handling
- [Adapter list](adapter-list.md) - choose a production-ready adapter
