# Advanced queue name internals

Use this reference when you need to understand how queue names map to adapters, how providers resolve queues, and how to implement your own provider.

## How queue names are used in code

- A queue name (string or `BackedEnum`) is passed to `Yiisoft\Queue\Provider\QueueProviderInterface::get($queueName)`.
- The provider returns a `Yiisoft\Queue\QueueInterface` instance configured for that name.
- `QueueInterface::getName()` can be used for introspection; it returns the logical name the queue was created with.

## Provider implementations

`QueueProviderInterface::get()` may throw the following exceptions when configuration is invalid:

- `Yiisoft\Queue\Provider\QueueNotFoundException`
- `Yiisoft\Queue\Provider\InvalidQueueConfigException`
- `Yiisoft\Queue\Provider\QueueProviderException`

This package ships four provider strategies:

### AdapterFactoryQueueProvider (default)

- Backed by the `yiisoft/queue.queues` params array.
- Each queue name maps to an adapter definition.
- Uses `yiisoft/factory` to create adapters lazily, then wraps them in a `Queue` with the given name.
- Enforces a strict name mapping: unknown queue names throw `QueueNotFoundException` immediately.

### PredefinedQueueProvider

- Accepts a pre-built map of queue name → `QueueInterface` instance.
- Useful when you already have fully constructed queue objects and want to register them by name.
- Throws `QueueNotFoundException` for unknown names, `InvalidQueueConfigException` if a value is not a `QueueInterface`.

Example:

```php
use Yiisoft\Queue\Provider\PredefinedQueueProvider;

$provider = new PredefinedQueueProvider([
    'emails' => $emailQueue,
    'reports' => $reportsQueue,
]);
$queueForEmails = $provider->get('emails');
```

### QueueFactoryProvider

- Creates queue objects from [yiisoft/factory](https://github.com/yiisoft/factory) definitions indexed by queue name.
- Lazily instantiates and caches queues on first access.
- Throws `QueueNotFoundException` for unknown names.

Example:

```php
use Yiisoft\Queue\Provider\QueueFactoryProvider;

$provider = new QueueFactoryProvider(
    [
        'emails' => [
            'class' => Queue::class,
            '__construct()' => [$worker, $loop, $logger, $pushDispatcher, $adapter],
        ],
    ],
    $container,
);
$queueForEmails = $provider->get('emails');
```

### CompositeQueueProvider

- Accepts multiple providers and queries them in order.
- The first provider whose `has()` returns true for the queue name wins.
- Useful for mixing multiple providers, for example combining adapter-based and pre-built queues.

Example:

```php
use Yiisoft\Queue\Provider\CompositeQueueProvider;
use Yiisoft\Queue\Provider\AdapterFactoryQueueProvider;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;

$provider = new CompositeQueueProvider(
    new AdapterFactoryQueueProvider($queue, $definitions, $container),
    new PredefinedQueueProvider(['fallback' => $fallbackQueue]),
);

$queueForEmails = $provider->get('emails');
```

## Implementing a custom provider

- Implement `QueueProviderInterface` if you need bespoke selection logic (e.g., tenant-specific routing, remote lookups, or metrics-aware routing).
- Register your provider in the DI container and swap it in wherever `QueueProviderInterface` is used.
- Consider exposing diagnostics (e.g., list of available queues) through `getNames()`, console commands, or health checks so operators can verify the available queues at runtime.
