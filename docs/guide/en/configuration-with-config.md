# Configuration with yiisoft/config

If you are using [yiisoft/config](https://github.com/yiisoft/config) (i.e. installed with [yiisoft/app](https://github.com/yiisoft/app) or [yiisoft/app-api](https://github.com/yiisoft/app-api)), you'll find out this package has some defaults in the [`common`](../../../config/di.php) and [`params`](../../../config/params.php) configurations saving your time.

## Where to put the configuration

In `yiisoft/app` / `yiisoft/app-api` templates you typically add or adjust configuration in `config/params.php`.
If your project structure differs, put configuration into any params config file that is loaded by `yiisoft/config`.

## What you need to configure

- Optionally: define default `\Yiisoft\Queue\Adapter\AdapterInterface` implementation.
- And/or define channel-specific `AdapterInterface` implementations in the `channels` params key to be used
  with the [queue provider](usage.md#different-queue-channels).
- Define [message handlers](worker.md#handler-format) in the `handlers` params key to be used with the `QueueWorker`.
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
    'yiisoft/yii-console' => [
        'commands' => [
            'queue:run' => \Yiisoft\Queue\Command\RunCommand::class,
            'queue:listen' => \Yiisoft\Queue\Command\ListenCommand::class,
            'queue:listen:all' => \Yiisoft\Queue\Command\ListenAllCommand::class,
        ],
    ],
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

## Console commands

If you are using `yiisoft/config` with `yiisoft/yii-console`, the component automatically registers the commands.

The following command obtains and executes tasks in a loop until the queue is empty:

```sh
yii queue:run [channel1 [channel2 [...]]] --maximum=100
```

The following command launches a daemon which infinitely queries the queue:

```sh
yii queue:listen [channel]
```

The following command iterates through multiple channels and is meant to be used in development environment only:

```sh
yii queue:listen:all [channel1 [channel2 [...]]] --pause=1 --maximum=0
```

For long-running processes, graceful shutdown is controlled by `LoopInterface`. When `ext-pcntl` is available,
the default `SignalLoop` handles signals such as `SIGTERM`/`SIGINT`.
