# Queue channels

A *queue channel* is a named queue configuration.

In practice, a channel is a string (for example, `yii-queue`, `emails`, `critical`) that selects which queue backend (adapter) messages are pushed to and which worker consumes them.

Having multiple channels is useful when you want to separate workloads, for example:

- **Different priorities**: `critical` vs `low`.
- **Different message types**: `emails`, `reports`, `webhooks`.
- **Different backends / connections**: fast Redis queue for short jobs and a different backend for long-running jobs.

The default channel name is `Yiisoft\Queue\QueueInterface::DEFAULT_CHANNEL` (`yii-queue`).

## How channels are used in the code

- A channel name is passed to `Yiisoft\Queue\Provider\QueueProviderInterface::get($channel)`.
- The provider returns a `Yiisoft\Queue\QueueInterface` instance bound to that channel.
- Internally, the provider creates an adapter instance and calls `AdapterInterface::withChannel($channel)`.

In other words, a channel is the key that lets the application select a particular adapter instance/configuration.

## Choosing a channel at runtime

### In CLI

These built-in commands accept channel names:

- `queue:listen [channel]` listens to a single channel (defaults to `yii-queue`).
- `queue:run [channel1 [channel2 [...]]]` processes existing messages and exits.
- `queue:listen-all [channel1 [channel2 [...]]]` iterates over multiple channels (meant mostly for development).

Examples:

```sh
yii queue:listen emails
yii queue:run critical emails --maximum=100
yii queue:listen-all critical emails --pause=1 --maximum=500
```

### In PHP code

When you have a `QueueProviderInterface`, request a queue by channel name:

```php
/** @var \Yiisoft\Queue\Provider\QueueProviderInterface $provider */

$emailsQueue = $provider->get('emails');
$emailsQueue->push(new \Yiisoft\Queue\Message\Message('send-email', ['to' => 'user@example.com']));
```

You can also check if a channel exists before trying to get it:

```php
if ($provider->has('emails')) {
    $emailsQueue = $provider->get('emails');
}
```

`QueueProviderInterface` accepts both strings and `BackedEnum` values (they are normalized to a string channel name).

`QueueProviderInterface::get()` may throw:

- `Yiisoft\Queue\Provider\ChannelNotFoundException`
- `Yiisoft\Queue\Provider\InvalidQueueConfigException`
- `Yiisoft\Queue\Provider\QueueProviderException`

## Providers

`QueueProviderInterface` is the component responsible for returning a `QueueInterface` instance bound to a particular channel.

Out of the box, this package provides three implementations:

- `Yiisoft\Queue\Provider\AdapterFactoryQueueProvider`
- `Yiisoft\Queue\Provider\PrototypeQueueProvider`
- `Yiisoft\Queue\Provider\CompositeQueueProvider`

### `AdapterFactoryQueueProvider`

This provider creates channel-specific `QueueInterface` instances based on adapter definitions.

It uses [`yiisoft/factory`](https://github.com/yiisoft/factory) to resolve adapter definitions.

This approach is recommended when you want:

- Separate configuration per channel.
- Stronger validation (unknown channels are not silently accepted).

### `PrototypeQueueProvider`

This provider always returns a queue by taking a base queue + base adapter and only changing the channel name.

This can be useful when all channels use the same adapter and only differ by channel name.

This strategy is not recommended as it does not give you any protection against typos and mistakes in channel names.

Example:

```php
use Yiisoft\Queue\Provider\PrototypeQueueProvider;

$provider = new PrototypeQueueProvider($queue, $adapter);

$queueForEmails = $provider->get('emails');
$queueForCritical = $provider->get('critical');
```

### `CompositeQueueProvider`

This provider combines multiple providers into one.

It tries to resolve a channel by calling `has()`/`get()` on each provider in the order they are passed to the constructor.
The first provider that reports it has the channel wins.

Example:

```php
use Yiisoft\Queue\Provider\CompositeQueueProvider;

$provider = new CompositeQueueProvider(
    $providerA,
    $providerB,
);

$queue = $provider->get('emails');
```

## Configuration with yiisoft/config
 
 When using [yiisoft/config](https://github.com/yiisoft/config), channel configuration is stored in params under `yiisoft/queue.channels`.

 By default, `QueueProviderInterface` is bound to `AdapterFactoryQueueProvider`.
 That makes `yiisoft/queue.channels` a strict channel registry:

 - `QueueProviderInterface::has($channel)` checks whether the channel exists in definitions.
 - `QueueProviderInterface::get($channel)` throws `ChannelNotFoundException` for unknown channels.

 The same channel list is used by `queue:run` and `queue:listen-all` as the default set of channels to process.
 
 It is a map:

- key: channel name
- value: adapter definition that should be resolved for that channel

Minimal example (single channel):

```php
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\QueueInterface;

return [
    'yiisoft/queue' => [
        'channels' => [
            QueueInterface::DEFAULT_CHANNEL => AdapterInterface::class,
        ],
    ],
];
```

Multiple channels example:

```php
use Yiisoft\Queue\QueueInterface;

return [
    'yiisoft/queue' => [
        'channels' => [
            QueueInterface::DEFAULT_CHANNEL => \Yiisoft\Queue\Adapter\AdapterInterface::class,
            'critical' => \Yiisoft\Queue\Adapter\AdapterInterface::class,
            'emails' => \Yiisoft\Queue\Adapter\AdapterInterface::class,
        ],
    ],
];
```

The exact adapter definitions depend on which queue adapter package you use (Redis, AMQP, etc.).

When using the default DI config from this package, the configured channel names are also used as the default channel list for `queue:run` and `queue:listen-all`.

## Manual configuration (without yiisoft/config)

For multiple channels without `yiisoft/config`, you can create a provider manually.

`AdapterFactoryQueueProvider` accepts adapter definitions indexed by channel names and returns a `QueueInterface` for a channel on demand:

```php
use Yiisoft\Queue\Provider\AdapterFactoryQueueProvider;
use Yiisoft\Queue\Adapter\SynchronousAdapter;

$definitions = [
    'channel1' => new SynchronousAdapter($worker, $queue),
    'channel2' => static fn (SynchronousAdapter $adapter) => $adapter->withChannel('channel2'),
    'channel3' => [
        'class' => SynchronousAdapter::class,
        '__constructor' => ['channel' => 'channel3'],
    ],
];

$provider = new AdapterFactoryQueueProvider(
    $queue,
    $definitions,
    $container,
);

$queueForChannel1 = $provider->get('channel1');
$queueForChannel2 = $provider->get('channel2');
$queueForChannel3 = $provider->get('channel3');
```

For more information about the definition formats available, see the [`yiisoft/factory` documentation](https://github.com/yiisoft/factory).
