# Configuration with [yiisoft/config](https://github.com/yiisoft/config)

If you are using [yiisoft/config](https://github.com/yiisoft/config) (i.e. installed with [yiisoft/app](https://github.com/yiisoft/app) or [yiisoft/app-api](https://github.com/yiisoft/app-api)), you'll find out this package has some defaults in the [`common`](../../../config/di.php) and [`params`](../../../config/params.php) configurations saving your time.

## Where to put the configuration

In [yiisoft/app](https://github.com/yiisoft/app)/[yiisoft/app-api](https://github.com/yiisoft/app-api) templates you typically add or adjust configuration in `config/params.php`.
If your project structure differs, put configuration into any params config file that is loaded by [yiisoft/config](https://github.com/yiisoft/config).

## What you need to configure

- Define queue channel adapter definitions in the `channels` params key. See more about channels [here](./channels.md).
- Optionally: define [message handlers](./message-handler.md) in the `handlers` params key to be used with the `QueueWorker`.
- Resolve other `\Yiisoft\Queue\Queue` dependencies (psr-compliant event dispatcher).

By default, when using the DI config provided by this package, `QueueProviderInterface` is bound to `AdapterFactoryQueueProvider` and uses `yiisoft/queue.channels` as a strict channel registry.
That means unknown channels are not accepted silently and `QueueProviderInterface::get()` will throw `ChannelNotFoundException`.
The configured channel names are also used as the default channel list for `queue:run` and `queue:listen-all`.

For development and testing you can start with the synchronous adapter.
For production you must use a real backend adapter (AMQP, Kafka, SQS, etc.). If you do not have any preference, start with [yiisoft/queue-amqp](https://github.com/yiisoft/queue-amqp) and [RabbitMQ](https://www.rabbitmq.com/).

The examples below use the synchronous adapter for brevity. In production, override `yiisoft/queue.channels` with an adapter definition from the backend adapter package you selected.

## Minimal configuration example

If you use the handler class FQCN as the message handler name, no additional configuration is required.

See [Message handler](./message-handler.md) for details and trade-offs.

## Minimal configuration example (named handlers)

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'handler-name' => [FooHandler::class, 'handle'],
        ],
    ],
];
```

## Full configuration example

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'handler-name' => [FooHandler::class, 'handle'],
        ],
        'channels' => [
            \Yiisoft\Queue\QueueInterface::DEFAULT_CHANNEL => \Yiisoft\Queue\Adapter\SynchronousAdapter::class,
        ],
        'middlewares-push' => [], // push middleware pipeline definition
        'middlewares-consume' => [], // consume middleware pipeline definition
        'middlewares-fail' => [], // consume failure handling middleware pipeline definition
    ],
];
```
Middleware pipelines are discussed in detail [here](./middleware-pipelines.md).
