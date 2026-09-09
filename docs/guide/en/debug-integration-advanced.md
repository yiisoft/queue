# Advanced Yii Debug integration

Use this guide to understand the optional native middleware and status-provider instrumentation used by the queue collector.

## What is collected

`Yiisoft\Queue\Debug\QueueCollector` can capture:

- pushed messages, grouped by their normalized queue key;
- status checks made through the producer status capability;
- worker processing events, grouped by queue key.

There is no consumer processing metric or debug proxy. Push instrumentation does not report status checks, and status instrumentation does not wrap push calls.

## How it works

The integration uses native middleware:

- `Yiisoft\Queue\Debug\Middleware\PushDebugMiddleware` wraps the push pipeline. After the downstream/final push handler returns, it records the message from the returned `PushRequest` and the immutable normalized identity from the incoming `PushRequest`. The returned message is not necessarily adapter output: synchronous pushing may process and replace it. If downstream processing throws, no push event is recorded; a middleware that short-circuits before this middleware is reached also bypasses it.
- `Yiisoft\Queue\Debug\Middleware\WorkerDebugMiddleware` runs in the separate worker pipeline and records processing before handler resolution. The processing event is recorded before downstream handling, so a later exception does not remove it; middleware that short-circuits before this middleware is reached prevents it.
- `Yiisoft\Queue\Debug\QueueProducerStatusProviderProxy` wraps only `QueueProducerStatusProviderInterface`. It decorates the status capability returned by `getStatus($queueName)`; status-provider operations are not push or consumer-processing instrumentation.

All of these are optional. Services instantiated directly are instrumented only when the corresponding middleware or provider wrapper is explicitly supplied.

## Manual configuration

When using [yiisoft/config](https://github.com/yiisoft/config), configure `middlewares-push` and `middlewares-worker` as needed. Register the status provider wrapper only if status instrumentation is wanted:

```php
use Yiisoft\Queue\Debug\Middleware\PushDebugMiddleware;
use Yiisoft\Queue\Debug\Middleware\WorkerDebugMiddleware;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueProducerStatusProviderProxy;
use Yiisoft\Queue\Provider\QueueProducerStatusProviderInterface;

return [
    'yiisoft/queue' => [
        'middlewares-push' => [PushDebugMiddleware::class],
        'middlewares-worker' => [WorkerDebugMiddleware::class],
    ],
    'yiisoft/yii-debug' => [
        'collectors' => [QueueCollector::class],
        'trackedServices' => [
            QueueProducerStatusProviderInterface::class => [
                QueueProducerStatusProviderProxy::class,
                QueueCollector::class,
            ],
        ],
    ],
];
```
