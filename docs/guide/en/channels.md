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

## Configuration with yiisoft/config

When using [yiisoft/config](https://github.com/yiisoft/config), channel configuration is stored in params under `yiisoft/queue.channels`.

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

$definitions = [
    'channel1' => new \Yiisoft\Queue\Adapter\SynchronousAdapter($worker, $queue),
    'channel2' => static fn (\Yiisoft\Queue\Adapter\SynchronousAdapter $adapter) => $adapter->withChannel('channel2'),
];

$provider = new AdapterFactoryQueueProvider(
    $queue,
    $definitions,
    $container,
);

$queueForChannel1 = $provider->get('channel1');
$queueForChannel2 = $provider->get('channel2');
```
