# Manual Configuration (without [yiisoft/config](https://github.com/yiisoft/config))

This guide explains how to set up the queue component manually, without using [yiisoft/config](https://github.com/yiisoft/config).

## Basic setup

To use the queue, you need to create instances of the following classes:

1. **Adapter** - handles the actual queue backend (e.g., `SynchronousAdapter`, or an adapter from external packages like Redis, AMQP, etc.)
2. **Worker** - processes messages from the queue
3. **Queue** - the main entry point for pushing messages

### Example

```php
use Yiisoft\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\MiddlewareFactoryConsume;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFactoryFailure;
use Yiisoft\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Psr\Container\ContainerInterface;

// You need a PSR-11 container for dependency injection
/** @var ContainerInterface $container */

// Define message handlers
$handlers = [
    'file-download' => [FileDownloader::class, 'handle'],
    FileDownloader::class => [FileDownloader::class, 'handle'],
];

// Create middleware dispatchers
$consumeMiddlewareDispatcher = new ConsumeMiddlewareDispatcher(
    new MiddlewareFactoryConsume($container),
);

$failureMiddlewareDispatcher = new FailureMiddlewareDispatcher(
    new MiddlewareFactoryFailure($container),
    [],
);

$pushMiddlewareDispatcher = new PushMiddlewareDispatcher(
    new MiddlewareFactoryPush($container),
);

// Create worker
$worker = new Worker(
    $handlers,
    $container->get(\Psr\Log\LoggerInterface::class),
    $container->get(\Yiisoft\Injector\Injector::class),
    $container,
    $consumeMiddlewareDispatcher,
    $failureMiddlewareDispatcher,
);

// Create queue with adapter
$queue = new Queue(
    $worker,
    $pushMiddlewareDispatcher,
    $container->get(\Psr\EventDispatcher\EventDispatcherInterface::class),
    new SynchronousAdapter($worker, /* queue instance will be set via withAdapter */),
);

// Now you can push messages
$message = new \Yiisoft\Queue\Message\Message('file-download', ['url' => 'https://example.com/file.pdf']);
$queue->push($message);
```

## Using Queue Provider

For multiple queue channels, use `AdapterFactoryQueueProvider`:

```php
use Yiisoft\Queue\Provider\AdapterFactoryQueueProvider;
use Yiisoft\Queue\Adapter\SynchronousAdapter;

$definitions = [
    'channel1' => new SynchronousAdapter($worker, $queue),
    'channel2' => static fn(SynchronousAdapter $adapter) => $adapter->withChannel('channel2'),
];

$provider = new AdapterFactoryQueueProvider(
    $queue,
    new \Yiisoft\Factory\Factory($container),
    $definitions,
);

$queueForChannel1 = $provider->get('channel1');
$queueForChannel2 = $provider->get('channel2');
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
- [Workers](worker.md) - understand handler formats
- [Error handling](error-handling.md) - configure retries and failure handling
- [Adapter list](adapter-list.md) - choose a production-ready adapter
