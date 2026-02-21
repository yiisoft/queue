# Queue names

A *queue name* is a named queue configuration (a logical namespace/identifier that separates one queue workload from another).

In practice, a queue name is a string (for example, `yii-queue`, `emails`, `critical`) that selects which queue backend (adapter) messages are pushed to and which worker consumes them.

At a high level:

- You configure one or more queue names.
- When producing messages, you either:
  - use `QueueInterface` directly (single/default queue), or
  - use `QueueProviderInterface` to get a queue for a specific queue name.
- When consuming messages, you run a worker command for a queue name (or a set of queue names).

Having multiple queue names is useful when you want to separate workloads, for example:

- **Different priorities**: `critical` vs `low`.
- **Different message types**: `emails`, `reports`, `webhooks`.
- **Different backends / connections**: fast Redis queue for short jobs and RabbitMQ backend for long-running jobs or inter-app communication.

The default queue name is `Yiisoft\Queue\QueueInterface::DEFAULT_CHANNEL` (`yii-queue`).

## Quick start (yiisoft/config)

When using [yiisoft/config](https://github.com/yiisoft/config), queue name configuration is stored in params under `yiisoft/queue.channels`.

### 1. Start with a single queue (default)

If you use only a single queue, you can inject `QueueInterface` directly.

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

#### 1.2. Configure a default queue name

When you are using `yiisoft/config` and the default configs from this package are loaded, the default queue name is already present in params (so you don't need to add anything). The snippet below shows what is shipped by default in [config/params.php](../../../config/params.php):

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

### 2. Multiple queue names

Add more queue names to the `params.php`:

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

If you have multiple queue names, inject `QueueProviderInterface` and call `get('queue-name')`.

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

`QueueProviderInterface` accepts both strings and `BackedEnum` values (they are normalized to a string queue name).

## Running workers (CLI)

To consume messages you run console commands such as `queue:run`, `queue:listen`, and `queue:listen-all`.
See [Console commands](console-commands.md) for details.

## Advanced

The sections below describe internal mechanics and advanced setups. You can skip them if you only need to configure and use queue names.

### How queue names are used in the code

- A queue name is passed to `Yiisoft\Queue\Provider\QueueProviderInterface::get($queueName)`.
- The provider returns a `Yiisoft\Queue\QueueInterface` instance that uses an adapter configured for that queue name.
- Internally, the provider creates an adapter instance and calls `AdapterInterface::withChannel($channel)`, where the channel is an adapter-specific characteristic that may or may not be the same as the queue name.

In other words, a queue name is the key that lets the application select a particular adapter instance/configuration.

`QueueInterface::getChannel()` is available for introspection and returns the adapter's internal channel identifier. The channel itself is stored in the adapter and `Queue` proxies it.

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
It creates `QueueInterface` instances based on adapter definitions indexed by queue name.

It uses [`yiisoft/factory`](https://github.com/yiisoft/factory) to resolve adapter definitions.

This approach is recommended when you want:

- Separate configuration per queue name.
- Stronger validation (unknown queue names are not silently accepted).

#### `PrototypeQueueProvider`

This provider always returns a queue by taking a base queue + base adapter and only changing the internal channel name.

This can be useful when all queue names use the same adapter and only differ by the internal channel identifier.

This strategy is not recommended as it does not give you any protection against typos and mistakes in queue names.

Example:

```php
use Yiisoft\Queue\Provider\PrototypeQueueProvider;

$provider = new PrototypeQueueProvider($queue, $adapter);

$queueForEmails = $provider->get('emails');
$queueForCritical = $provider->get('critical');
```

#### `CompositeQueueProvider`

This provider combines multiple providers into one.

It tries to resolve a queue name by calling `has()`/`get()` on each provider in the order they are passed to the constructor.
The first provider that reports it has the queue name wins.

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

For multiple queue names without `yiisoft/config`, you can create a provider manually.

`AdapterFactoryQueueProvider` accepts adapter definitions indexed by queue names and returns a `QueueInterface` for a queue name on demand:

> In this example, `$worker`, `$queue` and `$container` are assumed to be created already.
> See [Manual configuration](configuration-manual.md) for a full runnable setup.

```php
use Yiisoft\Queue\Provider\AdapterFactoryQueueProvider;
use Yiisoft\Queue\Adapter\SynchronousAdapter;

$definitions = [
    'queue1' => new SynchronousAdapter($worker, $queue, 'channel1'),
    'queue2' => new SynchronousAdapter($worker, $queue, 'channel2'),
    'queue3' => [
        'class' => SynchronousAdapter::class,
        '__construct()' => ['channel' => 'channel3'],
    ],
];

$provider = new AdapterFactoryQueueProvider(
    $queue,
    $definitions,
    $container,
);

$queueForQueue1 = $provider->get('queue1');
$queueForQueue2 = $provider->get('queue2');
$queueForQueue3 = $provider->get('queue3');
```

For more information about the definition formats available, see the [`yiisoft/factory` documentation](https://github.com/yiisoft/factory).
