Synchronous Mode
================

Run tasks synchronously in the same process. Useful for:

- developing and debugging an application;
- writing tests;
- production setups where the application is built around `QueueInterface` from day one but
  doesn't have an external broker yet — you can switch to a real adapter later without touching
  the call sites.

To enable it, construct the queue without an adapter (the `adapter` argument defaults to `null`):

```php
$logger = $DIContainer->get(\Psr\Log\LoggerInterface::class);

$worker = $DIContainer->get(\Yiisoft\Queue\Worker\WorkerInterface::class);
$loop = $DIContainer->get(\Yiisoft\Queue\Cli\LoopInterface::class);
$pushMiddlewareDispatcher = $DIContainer->get(
    \Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher::class
);

$queue = new Yiisoft\Queue\Queue(
    $worker,
    $loop,
    $logger,
    $pushMiddlewareDispatcher,
);
```

In synchronous mode every message passed to `push()` is processed immediately by the worker.
The value returned from `push()` is the message after push-middlewares — without an `IdEnvelope`,
since no adapter is involved to assign an ID.

Limitations:

- `run()` does nothing and returns `0`.
- `listen()` logs an info message and returns without listening.
- `status()` throws `BadMethodCallException` — there is no message storage to track IDs.
