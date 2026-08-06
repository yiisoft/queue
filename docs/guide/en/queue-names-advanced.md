# Advanced queue names and providers

A queue name is a logical message stream. It lets an application choose a producer and a consumer independently for that stream: a name may be producer-only, consumer-only, or have both capabilities. It is not itself a queue object and does not require both roles to use the same backend.

Most applications configure names through [`yiisoft/queue.queues`](queue-names.md) and inject the default producer directly. Use a provider when code must choose a named stream at runtime, a worker must resolve a named consumer, or your application constructs or combines queue registries itself.

## Providers and capabilities

Providers translate a queue name into the capability the caller needs:

- `QueueProducerProviderInterface::getProducer($name)` returns a `QueueProducerInterface` for pushing messages and obtaining their status.
- `QueueConsumerProviderInterface::getConsumer($name)` returns a `QueueConsumerInterface` for running or listening for messages.
- `hasProducer()` / `hasConsumer()` check whether a name exposes a role. `getProducerNames()` / `getConsumerNames()` list names for only that role.

Both lookup methods accept a string or `BackedEnum`. They throw `QueueNotFoundException` when the name is unknown or does not have the requested role. This separation prevents a producer-only queue from accidentally being used by a worker, and vice versa.

The default name is `QueueProducerProviderInterface::DEFAULT_QUEUE` (also available from `QueueConsumerProviderInterface`), whose value is `yii-queue`.

## Role-map configuration

The built-in providers use a strict role map: `queues[name][producer|consumer]`. Every name must contain at least one role, and no keys other than `producer` and `consumer` are valid. The values are either factory definitions or ready instances, depending on the provider.

Choose the provider by how the roles are created:

- Use `QueueFactoryProvider` when the values are [`yiisoft/factory`](https://github.com/yiisoft/factory) definitions. It creates and caches each role lazily, so resolving a producer does not construct the consumer for the same name.
- Use `PredefinedQueueProvider` when the values are already-built `QueueProducerInterface` or `QueueConsumerInterface` instances. It does not accept factory definitions.

`QueueFactoryProvider` is appropriate for container configuration:

```php
use Yiisoft\Queue\Provider\QueueFactoryProvider;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\AsyncQueueProducer;

$provider = new QueueFactoryProvider([
    'emails' => [
        'producer' => ['class' => AsyncQueueProducer::class],
        'consumer' => ['class' => QueueConsumer::class],
    ],
    'audit' => [
        'producer' => ['class' => AsyncQueueProducer::class],
    ],
], $container);

$emailProducer = $provider->getProducer('emails');
$emailConsumer = $provider->getConsumer('emails');
```

`PredefinedQueueProvider` is useful for manual wiring or tests, where the roles have already been constructed:

```php
use Yiisoft\Queue\Provider\PredefinedQueueProvider;

$provider = new PredefinedQueueProvider([
    'emails' => [
        'producer' => $emailProducer,
        'consumer' => $emailConsumer,
    ],
    'audit' => ['producer' => $auditProducer],
]);
```

For configuration through `yiisoft/config`, see [Queue names](queue-names.md). For manual construction of producers and consumers, see [Manual configuration](configuration-manual.md).

## Combining and extending providers

`CompositeQueueProvider` combines providers. It checks providers in constructor order and uses the first one that has the requested capability. Precedence is per role, so one provider can supply a producer while another supplies the consumer for the same name.

```php
use Yiisoft\Queue\Provider\CompositeQueueProvider;

$provider = new CompositeQueueProvider($applicationQueues, $fallbackQueues);
$producer = $provider->getProducer('emails');
$consumer = $provider->getConsumer('inbound-events');
```

Use a composite provider for layered configuration, such as application-specific queues with a fallback registry. Implement `QueueProducerProviderInterface`, `QueueConsumerProviderInterface`, or both when names come from another source—for example, a tenant-aware registry or an external configuration service. Register the typed interface that your caller needs in DI; do not expose a generic queue lookup.
