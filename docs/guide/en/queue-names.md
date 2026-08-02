# Queue names

A *queue name* is a logical identifier for independently configured producer and consumer capabilities. A name can have a producer, a consumer, or both; it does not imply that the two roles use the same object.

- Inject `QueueProducerInterface` to push messages to the default queue.
- Use `QueueProducerProviderInterface` to obtain a named producer with `getProducer()`.
- Use `QueueConsumerProviderInterface` to obtain a named consumer with `getConsumer()`; console commands use this provider.

The default name is `QueueProducerProviderInterface::DEFAULT_QUEUE` (also available from `QueueConsumerProviderInterface`) and is `yii-queue`.

## When to use named queues

Use the default queue when all messages can share the same transport and worker behavior. Add names when messages need separate operational treatment: for example, to send high-priority and background work to different backends, run workers independently, or exchange only one message stream with another application. A name is an application-level routing decision; the `producer` and `consumer` roles under that name define how messages enter and leave that stream.

## Configuration

Named queues use a strict role map under `yiisoft/queue.queues`. Each name must contain one or both of `producer` and `consumer`; flat adapter or factory definitions are not supported.

```php
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\QueueProducer;

return [
    'yiisoft/queue' => [
        'queues' => [
            // A queue with both capabilities.
            QueueProducerProviderInterface::DEFAULT_QUEUE => [
                'producer' => ['class' => QueueProducer::class, '__construct()' => ['adapter' => AdapterInterface::class]],
                'consumer' => ['class' => QueueConsumer::class, '__construct()' => ['adapter' => AdapterInterface::class]],
            ],
            // Produce-only and consume-only names are valid.
            'outbound-events' => [
                'producer' => ['class' => QueueProducer::class, '__construct()' => ['adapter' => AdapterInterface::class]],
            ],
            'inbound-events' => [
                'consumer' => ['class' => QueueConsumer::class, '__construct()' => ['adapter' => AdapterInterface::class]],
            ],
        ],
    ],
];
```

`QueueFactoryProvider` resolves these role definitions lazily and caches each role independently. `PredefinedQueueProvider` has the same shape, but each value must be an already-created instance of its role interface.

## Producing messages

For the default queue, inject the producer directly:

```php
use Yiisoft\Queue\QueueProducerInterface;

final readonly class SendWelcomeEmail
{
    public function __construct(private QueueProducerInterface $queue) {}

    public function run(string $email): void
    {
        $this->queue->push(new SendEmailMessage(to: $email));
    }
}
```

For a named producer, request the producer capability explicitly:

```php
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;

final readonly class SendTransactionalEmail
{
    public function __construct(private QueueProducerProviderInterface $queues) {}

    public function run(string $email): void
    {
        $this->queues->getProducer('outbound-events')->push(new SendEmailMessage(to: $email));
    }
}
```

Both typed providers accept strings and `BackedEnum` values. Use `getProducerNames()` or `getConsumerNames()` when enumerating only that role.

## Running workers

`queue:run` and `queue:listen-all` use all configured consumer names when no names are supplied. `queue:listen` and explicitly named commands resolve that name through `QueueConsumerProviderInterface`, so a producer-only name cannot be consumed. See [Console commands](console-commands.md) for details.

For provider implementations and manually constructed role maps, see [Advanced queue name internals](queue-names-advanced.md). For migration details, see [Producer and consumer capabilities](queue-capabilities.md).
