# Synchronous Mode

Run tasks synchronously in the same process. Useful for:

- developing and debugging an application;
- writing tests;
- production setups where the application is built around a producer from day one but
  doesn't have an external broker yet — you can switch to a real adapter later without changing
  the producer call sites.

To enable it, use `SyncQueueProducer` instead of `AsyncQueueProducer` — it takes a worker instead of an adapter:

```php
$logger = $DIContainer->get(\Psr\Log\LoggerInterface::class);

$worker = $DIContainer->get(\Yiisoft\Queue\Worker\Worker::class);
$pushMiddlewareConfig = $DIContainer->get(
    \Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig::class
);

$producer = new \Yiisoft\Queue\SyncQueueProducer(
    $logger,
    $pushMiddlewareConfig,
    $worker,
);
```

In synchronous mode every message passed to `$producer->push()` is processed immediately by the worker.
The value returned from `push()` is the final result from the Push handler after worker processing, including
handler, consume, and failure transformations — without an `IdEnvelope`, since no adapter is involved to assign an ID.

Limitations:

- A separately configured `QueueConsumer` without an adapter has `run()` return `0`.
- Its `listen()` logs an info message and returns without listening.
- `getStatus()->status()` always returns `MessageStatus::NOT_FOUND` — there is no message storage to track IDs.
