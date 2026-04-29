# Advanced Yii Debug integration

Use this guide when you need to understand which events are tracked by the queue collector, how proxy services operate, and how to wire the collector manually.

## What is collected

The integration is based on `Yiisoft\Queue\Debug\QueueCollector` and captures:

- Pushed messages grouped by queue name.
- Message status checks performed via `QueueInterface::status()`.
- Messages processed by a worker grouped by queue name.

## How it works

The collector is enabled by registering it in Yii Debug and wrapping tracked services with proxy implementations.

Out of the box (see this package's `config/params.php`), the following services are wrapped:

- `Yiisoft\Queue\Provider\QueueProviderInterface` is wrapped with `Yiisoft\Queue\Debug\QueueProviderInterfaceProxy`. The proxy decorates returned queues with `Yiisoft\Queue\Debug\QueueDecorator` so that `push()` and `status()` calls are reported to the collector.
- `Yiisoft\Queue\Worker\WorkerInterface` is wrapped with `Yiisoft\Queue\Debug\QueueWorkerInterfaceProxy` to record message processing events.

To see data in the debug panel, obtain `QueueProviderInterface` and `WorkerInterface` from the DI container — the debug proxies are registered there and will not be active if the services are instantiated directly.

## Manual configuration

If you do not rely on the defaults supplied via [yiisoft/config](https://github.com/yiisoft/config), configure the collector and proxies explicitly:

```php
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueProviderInterfaceProxy;
use Yiisoft\Queue\Debug\QueueWorkerInterfaceProxy;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\Worker\WorkerInterface;

return [
    'yiisoft/yii-debug' => [
        'collectors' => [
            QueueCollector::class,
        ],
        'trackedServices' => [
            QueueProviderInterface::class => [QueueProviderInterfaceProxy::class, QueueCollector::class],
            WorkerInterface::class => [QueueWorkerInterfaceProxy::class, QueueCollector::class],
        ],
    ],
];
```
