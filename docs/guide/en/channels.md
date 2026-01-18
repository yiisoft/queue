# Queue channels

A *queue channel* is a named queue configuration.

In practice, a channel is a string (for example, `yii-queue`, `emails`, `critical`) that selects which queue backend (adapter) messages are pushed to and which worker consumes them.

At a high level:

- You configure one or more channels.
- When producing messages, you either:
  - use `QueueInterface` directly (single/default channel), or
  - use `QueueProviderInterface` to get a queue for a specific channel.
- When consuming messages, you run a worker command for a channel (or a set of channels).

Having multiple channels is useful when you want to separate workloads, for example:

- **Different priorities**: `critical` vs `low`.
- **Different message types**: `emails`, `reports`, `webhooks`.
- **Different backends / connections**: fast Redis queue for short jobs and RabbitMQ backend for long-running jobs or inter-app communication.

The default channel name is `Yiisoft\Queue\QueueInterface::DEFAULT_CHANNEL` (`yii-queue`).

## Quick start (yiisoft/config)

When using [yiisoft/config](https://github.com/yiisoft/config), channel configuration is stored in params under `yiisoft/queue.channels`.

### 1. Start with a single channel (default)

If you use only a single channel, you can inject `QueueInterface` directly.

#### 1.1 Configure an Adapter

Adapter is what actually sends messages to a queue broker.

Minimal DI configuration example:

```php
use Yiisoft\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Queue\Adapter\AdapterInterface;

return [
    AdapterInterface::class => SynchronousAdapter::class,
];
```
> `SynchronousAdapter` is for learning/testing only. For production, install a real adapter, see adapter list: [adapter-list](adapter-list.md).

#### 1.2. Configure a default channel

When you are using `yiisoft/config` and the default configs from this package are loaded, the default channel is already present in params (so you don't need to add anything). The snippet below shows what is shipped by default in [config/params.php](../../../config/params.php):

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

Pushing a message via DI:

```php
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Message\Message;

final readonly class SendWelcomeEmail
{
    public function __construct(private QueueInterface $queue)
    {
    }

    public function run(string $email): void
    {
        $this->queue->push(new Message('send-email', ['to' => $email]));
    }
}
```

### 2. Multiple channels

Add more channels to the `params.php`:

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

If you have multiple channels, inject `QueueProviderInterface` and call `get('channel-name')`.

```php
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\Message\Message;

final readonly class SendTransactionalEmail
{
    public function __construct(private QueueProviderInterface $queueProvider)
    {
    }

    public function run(string $email): void
    {
        $this->queueProvider
            ->get('emails')
            ->push(new Message('send-email', ['to' => $email]));
    }
}
```

`QueueProviderInterface` accepts both strings and `BackedEnum` values (they are normalized to a string channel name).

## Running workers (CLI)

To consume messages you run console commands such as `queue:run`, `queue:listen`, and `queue:listen-all`.
See [Console commands](console-commands.md) for details.

## Advanced

### How channels are used in the code

- A channel name is passed to `Yiisoft\Queue\Provider\QueueProviderInterface::get($channel)`.
- The provider returns a `Yiisoft\Queue\QueueInterface` instance that uses an adapter configured for that channel.
- Internally, the provider creates an adapter instance and calls `AdapterInterface::withChannel($channel)`.

In other words, a channel is the key that lets the application select a particular adapter instance/configuration.

`QueueInterface::getChannel()` is available for introspection and higher-level logic (for example, selecting middleware pipelines per channel). The channel itself is stored in the adapter and `Queue` proxies it.

### Providers

`QueueProviderInterface::get()` may throw:

- `Yiisoft\Queue\Provider\ChannelNotFoundException`
- `Yiisoft\Queue\Provider\InvalidQueueConfigException`
- `Yiisoft\Queue\Provider\QueueProviderException`

Out of the box, this package provides three implementations:

- `Yiisoft\Queue\Provider\AdapterFactoryQueueProvider`
- `Yiisoft\Queue\Provider\PrototypeQueueProvider`
- `Yiisoft\Queue\Provider\CompositeQueueProvider`

#### `AdapterFactoryQueueProvider` (default)

`AdapterFactoryQueueProvider` is used by default when you use `yiisoft/config`.
It creates `QueueInterface` instances based on adapter definitions indexed by channel name.

It uses [`yiisoft/factory`](https://github.com/yiisoft/factory) to resolve adapter definitions.

This approach is recommended when you want:

- Separate configuration per channel.
- Stronger validation (unknown channels are not silently accepted).

#### `PrototypeQueueProvider`

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

#### `CompositeQueueProvider`

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

### Manual configuration (without yiisoft/config)

For multiple channels without `yiisoft/config`, you can create a provider manually.

`AdapterFactoryQueueProvider` accepts adapter definitions indexed by channel names and returns a `QueueInterface` for a channel on demand:

> In this example, `$worker`, `$queue` and `$container` are assumed to be created already.
> See [Manual configuration](configuration-manual.md) for a full runnable setup.

```php
use Yiisoft\Queue\Provider\AdapterFactoryQueueProvider;
use Yiisoft\Queue\Adapter\SynchronousAdapter;

$definitions = [
    'channel1' => new SynchronousAdapter($worker, $queue, 'channel1'),
    'channel2' => new SynchronousAdapter($worker, $queue, 'channel2'),
    'channel3' => [
        'class' => SynchronousAdapter::class,
        '__construct()' => ['channel' => 'channel3'],
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
