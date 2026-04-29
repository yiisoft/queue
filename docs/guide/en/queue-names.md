# Queue names

A *queue name* is a logical namespace/identifier that maps to a queue configuration.

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
- **Different backends / connections**: fast Redis queue for short messages and RabbitMQ backend for long-running messages or inter-app communication.

The default queue name is `Yiisoft\Queue\Provider\QueueProviderInterface::DEFAULT_QUEUE` (`yii-queue`).

## Quick start (yiisoft/config)

When using [yiisoft/config](https://github.com/yiisoft/config), queue name configuration is stored in params under `yiisoft/queue.queues`.

### 1. Start with a single queue (default)

If you use only a single queue, you can inject `QueueInterface` directly.

#### 1.1 Configure an Adapter

An adapter is what actually delivers messages to a queue broker. Pick one from the
[adapter list](adapter-list.md), install it, and bind it in DI:

```php
use Yiisoft\Queue\Adapter\AdapterInterface;

return [
    AdapterInterface::class => YourBrokerAdapter::class,
];
```

Refer to the chosen adapter's documentation for connection settings and any additional bindings.

If you don't have a broker yet, you can skip this step — the queue will run in
[synchronous mode](synchronous-mode.md) and process messages immediately on `push()`. You can
plug in a real adapter later without changing any call sites.

#### 1.2. Configure a default queue name

When you are using `yiisoft/config` and the default configs from this package are loaded, the default queue name is already present in params (so you don't need to add anything). The snippet below shows what is shipped by default in [config/params.php](../../../config/params.php):

```php
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Provider\QueueProviderInterface;

return [
    'yiisoft/queue' => [
        'queues' => [
            QueueProviderInterface::DEFAULT_QUEUE => AdapterInterface::class,
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
use Yiisoft\Queue\Provider\QueueProviderInterface;

return [
    'yiisoft/queue' => [
        'queues' => [
            QueueProviderInterface::DEFAULT_QUEUE => \Yiisoft\Queue\Adapter\AdapterInterface::class,
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

`QueueProviderInterface` accepts both strings and `BackedEnum` values. `BackedEnum` values are normalized to strings — string-backed enums use their backing value directly, while int-backed enums are cast to string.

```php
enum QueueChannel: string
{
    case Emails = 'emails';
    case Reports = 'reports';
}

// Using enum value:
$queueProvider->get(QueueChannel::Emails); // resolves to 'emails'
```

## Running workers (CLI)

To consume messages you run console commands such as `queue:run`, `queue:listen`, and `queue:listen-all`.
See [Console commands](console-commands.md) for details.

## Advanced queues and providers

For adapter factories, provider registries, and custom error handling strategies see [Advanced queue name internals](queue-names-advanced.md).
