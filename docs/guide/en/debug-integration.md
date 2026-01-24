# Yii Debug integration

This package provides an integration with [yiisoft/yii-debug](https://github.com/yiisoft/yii-debug).

When debug is enabled, it collects queue-related information and shows it in the Yii Debug panel.

## What is collected

The integration is based on `Yiisoft\Queue\Debug\QueueCollector`.

It collects:

- Pushed messages grouped by queue channel (including middleware definitions passed to `push()`).
- Job status checks performed via `QueueInterface::status()`.
- Messages processed by a worker grouped by queue channel.

## How it works

The details below are optional. You can skip them if you only need to enable the panel and see collected data.

The integration is enabled by registering the collector and wrapping tracked services with proxy implementations.

In this package defaults (see `config/params.php`), the following services are tracked:

- `Yiisoft\Queue\Provider\QueueProviderInterface` is wrapped with `Yiisoft\Queue\Debug\QueueProviderInterfaceProxy`.
  The proxy decorates returned queues with `Yiisoft\Queue\Debug\QueueDecorator` to collect `push()` and `status()` calls.
- `Yiisoft\Queue\Worker\WorkerInterface` is wrapped with `Yiisoft\Queue\Debug\QueueWorkerInterfaceProxy` to collect message processing.

Because of that, to see data in debug you should obtain `QueueProviderInterface` / `WorkerInterface` from the DI container.

## Configuration

If you use [yiisoft/config](https://github.com/yiisoft/config) and the configuration plugin, these defaults are loaded automatically from this package.

Otherwise, you can configure it manually in your params configuration:

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
