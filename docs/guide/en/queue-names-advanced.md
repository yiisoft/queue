# Advanced queue name internals

Use this reference when you need to understand how queue names map to adapters, how providers resolve channels, and how to extend the registry.

## How queue names are used in code

- A queue name (string or `BackedEnum`) is passed to `Yiisoft\Queue\Provider\QueueProviderInterface::get($queueName)`.
- The provider returns a `Yiisoft\Queue\QueueInterface` instance configured for that name.
- Internally, the provider typically constructs an adapter definition via [`yiisoft/factory`](https://github.com/yiisoft/factory) and calls `AdapterInterface::withChannel($channel)` to switch the broker-specific channel or queue.
- `QueueInterface::getChannel()` can be used for introspection; it proxies the adapter’s internal channel identifier.

## Provider implementations

`QueueProviderInterface::get()` may throw the following exceptions when configuration is invalid:

- `Yiisoft\Queue\Provider\ChannelNotFoundException`
- `Yiisoft\Queue\Provider\InvalidQueueConfigException`
- `Yiisoft\Queue\Provider\QueueProviderException`

This package ships three provider strategies:

### AdapterFactoryQueueProvider (default)

- Backed by the `yiisoft/queue.channels` params array.
- Each queue name maps to an adapter definition.
- Uses `yiisoft/factory` to create adapters lazily.
- Enforces a strict registry: unknown queue names throw `ChannelNotFoundException` immediately.

### PrototypeQueueProvider

- Starts from a base queue/adapter and only changes the internal channel (via `withChannel`).
- Handy when all queues share the same adapter configuration but route to different broker channels.
- Offers no validation against typos, so prefer factory-based providers when possible.

Example:

```php
use Yiisoft\Queue\Provider\PrototypeQueueProvider;

$provider = new PrototypeQueueProvider($queue, $adapter);
$queueForEmails = $provider->get('emails');
```

### CompositeQueueProvider

- Accepts multiple providers and queries them in order.
- The first provider that reports it has the queue name (`has()`/`get()`) wins.
- Useful for mixing strict registries with prototype-based fallbacks.

Example:

```php
use Yiisoft\Queue\Provider\CompositeQueueProvider;
use Yiisoft\Queue\Provider\AdapterFactoryQueueProvider;
use Yiisoft\Queue\Provider\PrototypeQueueProvider;

$provider = new CompositeQueueProvider(
    new AdapterFactoryQueueProvider($queue, $definitions, $container),
    new PrototypeQueueProvider($queue, $adapter),
);

$queueForEmails = $provider->get('emails');
```

## Extending the registry

- Implement `QueueProviderInterface` if you need bespoke selection logic (e.g., tenant-specific registries, remote lookups, or metrics-aware routing).
- Register your provider in the DI container and swap it in wherever `QueueProviderInterface` is used.
- Consider exposing diagnostics (e.g., list of available queues) through console commands or health checks so operators can verify the registry at runtime.
