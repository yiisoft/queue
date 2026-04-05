# Configuration with [yiisoft/config](https://github.com/yiisoft/config)

If you are using [yiisoft/config](https://github.com/yiisoft/config) (i.e. installed with [yiisoft/app](https://github.com/yiisoft/app) or [yiisoft/app-api](https://github.com/yiisoft/app-api)), you'll find out this package has some defaults in the [`common`](../../../config/di.php) and [`params`](../../../config/params.php) configurations saving your time.

## Where to put the configuration

In [yiisoft/app](https://github.com/yiisoft/app) / [yiisoft/app-api](https://github.com/yiisoft/app-api) templates you typically add or adjust configuration in `config/params.php`.
If your project structure differs, put configuration into any params config file that is loaded by [yiisoft/config](https://github.com/yiisoft/config).

When your message uses a handler class that implements `Yiisoft\Queue\Message\MessageHandlerInterface` and the handler name equals its FQCN, nothing else has to be configured: the DI container resolves the class automatically. See [Message handler: simple setup](message-handler-simple.md) for details and trade-offs.

Advanced applications eventually need the following tweaks:

- **Queue names** — configure queue/back-end per logical queue name via [`yiisoft/queue.queues` config](queue-names.md) when you need to parallelize message handling or send some of them to a different application.
- **Named handlers or callable definitions** — map a short handler name to a callable in [`yiisoft/queue.handlers` config](message-handler.md) when another application is the message producer and you cannot use FQCN as the handler name.
- **Middleware pipelines** — adjust push/consume/failure behavior: collect metrics, modify messages, and so on. See [Middleware pipelines](middleware-pipelines.md) for details.

For development and testing you can start with the synchronous adapter.
For production you have to use a [real backend adapter](adapter-list.md) (AMQP, Kafka, SQS, etc.). If you do not have any preference, it's simpler to start with [yiisoft/queue-amqp](https://github.com/yiisoft/queue-amqp) and [RabbitMQ](https://www.rabbitmq.com/).
