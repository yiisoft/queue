# Synchronous Mode

Run tasks synchronously in the same process. Useful for:

- developing and debugging an application;
- writing tests;
- production setups where the application is built around `QueueProducerInterface` from day one but
  doesn't have an external broker yet — you can switch to a real adapter later without touching
  the call sites.

To enable it, create the queue instance without an adapter (the `adapter` argument defaults to `null`):

```php
$logger = $DIContainer->get(\Psr\Log\LoggerInterface::class);

$worker = $DIContainer->get(\Yiisoft\Queue\Worker\WorkerInterface::class);
$pushMiddlewareConfig = $DIContainer->get(
    \Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig::class
);

$producer = new \Yiisoft\Queue\QueueProducer(
    $logger,
    $pushMiddlewareConfig,
    worker: $worker,
);
```

In synchronous mode every message passed to `$producer->push()` is processed immediately by the worker.
The value returned from `push()` is the message after push-middlewares — without an `IdEnvelope`,
since no adapter is involved to assign an ID.

Limitations:

- A separately configured `QueueConsumer` without an adapter has `run()` return `0`.
- Its `listen()` logs an info message and returns without listening.
- `status()` always returns `MessageStatus::NOT_FOUND` — there is no message storage to track IDs.
