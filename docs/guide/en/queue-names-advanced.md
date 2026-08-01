# Advanced queue name internals

Use typed providers to resolve a capability for a logical queue name:

- `QueueProducerProviderInterface::getProducer($name)` returns `QueueProducerInterface`.
- `QueueConsumerProviderInterface::getConsumer($name)` returns `QueueConsumerInterface`.
- `hasProducer()` / `hasConsumer()` and `getProducerNames()` / `getConsumerNames()` are role-specific.

Both lookups accept a string or `BackedEnum`. They throw `QueueNotFoundException` when the name or requested role is absent and may throw `InvalidQueueConfigException` for an invalid role definition.

## Provider implementations

### `QueueFactoryProvider`

`QueueFactoryProvider` accepts strict nested role maps whose values are [yiisoft/factory](https://github.com/yiisoft/factory) definitions. It creates and caches a producer and consumer separately, so a failure to create one role does not instantiate the other.

```php
use Yiisoft\Queue\Provider\QueueFactoryProvider;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\QueueProducer;

$provider = new QueueFactoryProvider([
    'emails' => [
        'producer' => ['class' => QueueProducer::class],
        'consumer' => ['class' => QueueConsumer::class],
    ],
    'reports' => [
        'producer' => ['class' => QueueProducer::class],
    ],
], $container);

$emailProducer = $provider->getProducer('emails');
$emailConsumer = $provider->getConsumer('emails');
```

A raw definition such as `'emails' => ['class' => QueueProducer::class]`, an empty map, or keys other than `producer` and `consumer` is invalid.

### `PredefinedQueueProvider`

`PredefinedQueueProvider` uses the same map shape for objects that have already been built. It does not accept factory definitions.

```php
use Yiisoft\Queue\Provider\PredefinedQueueProvider;

$provider = new PredefinedQueueProvider([
    'emails' => [
        'producer' => $emailProducer,
        'consumer' => $emailConsumer,
    ],
    'audit' => ['producer' => $auditProducer],
]);

$auditProducer = $provider->getProducer('audit');
```

Every `producer` value must implement `QueueProducerInterface`; every `consumer` value must implement `QueueConsumerInterface`.

### `CompositeQueueProvider`

`CompositeQueueProvider` combines typed providers. Earlier providers take precedence independently for each capability, which makes it possible to mix producer-only, consumer-only, and dual-role providers.

```php
use Yiisoft\Queue\Provider\CompositeQueueProvider;

$provider = new CompositeQueueProvider($applicationQueues, $fallbackQueues);
$producer = $provider->getProducer('emails');
$consumer = $provider->getConsumer('inbound-events');
```

## Custom providers

Implement `QueueProducerProviderInterface`, `QueueConsumerProviderInterface`, or both, according to the capability your integration exposes. Do not expose a generic queue lookup: callers must request `getProducer()` or `getConsumer()` explicitly.
