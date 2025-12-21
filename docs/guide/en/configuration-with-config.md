# Configuration with [yiisoft/config](https://github.com/yiisoft/config)

If you are using [yiisoft/config](https://github.com/yiisoft/config) (i.e. installed with [yiisoft/app](https://github.com/yiisoft/app) or [yiisoft/app-api](https://github.com/yiisoft/app-api)), you'll find out this package has some defaults in the [`common`](../../../config/di.php) and [`params`](../../../config/params.php) configurations saving your time.

## Where to put the configuration

In [yiisoft/app](https://github.com/yiisoft/app)/[yiisoft/app-api](https://github.com/yiisoft/app-api) templates you typically add or adjust configuration in `config/params.php`.
If your project structure differs, put configuration into any params config file that is loaded by [yiisoft/config](https://github.com/yiisoft/config).

## What you need to configure

- Optionally: define default `\Yiisoft\Queue\Adapter\AdapterInterface` implementation.
- And/or define channel-specific `AdapterInterface` implementations in the `channels` params key. See more about channels [here](./channels.md).
- Define [message handlers](./message-handlers.md) in the `handlers` params key to be used with the `QueueWorker`.
- Resolve other `\Yiisoft\Queue\Queue` dependencies (psr-compliant event dispatcher).

## Minimal configuration example

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
            \Yiisoft\Queue\QueueInterface::DEFAULT_CHANNEL => \Yiisoft\Queue\Adapter\AdapterInterface::class,
        ],
        'middlewares-push' => [],
        'middlewares-consume' => [],
        'middlewares-fail' => [],
    ],
];
```
