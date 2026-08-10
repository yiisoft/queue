# Queue producer and consumer capabilities

A logical queue name can independently expose a producer, a consumer, or both. Inject `QueueProducerInterface` to push/status messages and `QueueConsumerInterface` to run/listen. Console commands use only `QueueConsumerProviderInterface`; retry middleware uses a direct `QueueProducerInterface` or `QueueProducerProviderInterface`.

Named providers use a strict nested role map. `getProducerNames()` and `getConsumerNames()` return only names with that role. Role definitions are created lazily and cached per name and role; failed lazy creation is cached and repeated lookups rethrow the same configuration error.

```php
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\AsyncQueueProducer;

$definitions = [
    'orders' => [
        'producer' => ['class' => AsyncQueueProducer::class],
        'consumer' => ['class' => QueueConsumer::class],
    ],
    'outbound-events' => ['producer' => ['class' => AsyncQueueProducer::class]],
    'inbound-events' => ['consumer' => ['class' => QueueConsumer::class]],
];
```

`QueueFactoryProvider` accepts factory definitions in each role. `PredefinedQueueProvider` uses the same outer shape but each role value must already be its respective interface instance. A raw definition such as `'orders' => ['class' => AsyncQueueProducer::class]`, an empty role map, and unknown role keys are invalid.

`QueueInterface`, `Queue`, and `QueueProviderInterface` were removed before release. Replace them with `QueueProducerInterface`, `SyncQueueProducer` / `AsyncQueueProducer` / `QueueConsumer`, and the relevant typed provider. Synchronous consumers retain no-op `run()` and `listen()` behavior when no adapter is configured. Default retry of an asynchronously consumed message resolves a producer for the execution queue name through a configured producer provider; if none is available it fails with an actionable configuration error rather than dropping the message.
