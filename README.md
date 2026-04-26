<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Queue</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/queue/v/stable.svg)](https://packagist.org/packages/yiisoft/queue)
[![Total Downloads](https://poser.pugx.org/yiisoft/queue/downloads.svg)](https://packagist.org/packages/yiisoft/queue)
[![Build status](https://github.com/yiisoft/queue/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/yiisoft/queue/actions/workflows/build.yml?query=branch%3Amaster)
[![Code coverage](https://codecov.io/gh/yiisoft/queue/graph/badge.svg?token=NU2ST01B1U)](https://codecov.io/gh/yiisoft/queue)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fqueue%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/queue/master)
[![Static analysis](https://github.com/yiisoft/queue/actions/workflows/static.yml/badge.svg?branch=master)](https://github.com/yiisoft/queue/actions/workflows/static.yml?query=branch%3Amaster)
[![type-coverage](https://shepherd.dev/github/yiisoft/queue/coverage.svg)](https://shepherd.dev/github/yiisoft/queue)

An extension for running tasks asynchronously via queues.

## Requirements

- PHP 8.1 - 8.5.
- PCNTL extension for signal handling _(optional, recommended for production use)_.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/queue
```

## Quick Start

### 1. Install an adapter

For production use, you should install an adapter package that matches your message broker ([AMQP](https://github.com/yiisoft/queue-amqp), [Kafka](https://github.com/g41797/queue-kafka), [NATS](https://github.com/g41797/queue-nats), and [others](docs/guide/en/adapter-list.md)).
See the [adapter list](docs/guide/en/adapter-list.md) and follow the adapter-specific documentation for installation and configuration details.

> If you don't have an external broker — whether for development, testing, or because you want to
> design around `QueueInterface` from day one and add a real broker later — you can run the queue
> in [synchronous mode](docs/guide/en/synchronous-mode.md) (the adapter argument is optional).
> In this mode messages are processed immediately in the same process, so it won't provide true
> async execution, but the code stays the same when you switch to a real adapter.

### 2. Configure the queue

#### Configuration with [yiisoft/config](https://github.com/yiisoft/config)

**If you use [yiisoft/app](https://github.com/yiisoft/app) or [yiisoft/app-api](https://github.com/yiisoft/app-api)**

Add queue configuration to your application `$params` config. In [yiisoft/app](https://github.com/yiisoft/app)/[yiisoft/app-api](https://github.com/yiisoft/app-api) templates it's typically the `config/params.php` file.
_If your project structure differs, put it into any params config file that is loaded by [yiisoft/config](https://github.com/yiisoft/config)._

Minimal configuration example:

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'message-type' => [FooHandler::class, 'handle'],
        ],
    ],
];
```

[Advanced configuration with `yiisoft/config`](docs/guide/en/configuration-with-config.md)

#### Manual configuration

For setting up all classes manually, see the [Manual configuration](docs/guide/en/configuration-manual.md) guide.

### 3. Prepare a handler

You need to create a handler class that will process the queue messages. The most simple way is to implement the `MessageHandlerInterface`. Let's create an example for remote file processing:

```php
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageHandlerInterface;

final readonly class RemoteFileHandler implements MessageHandlerInterface
{
    // These dependencies will be resolved on handler creation by the DI container
    public function __construct(
        private FileDownloader $downloader,
        private FileProcessor $processor,
    ) {}

    // Every received message will be processed by this method
    public function handle(MessageInterface $downloadMessage): void
    {
        $url = $downloadMessage->getData()['url'];
        $localPath = $this->downloader->download($url);
        $this->processor->process($localPath);
    }
}
```

### 4. Send (produce/push) a message to a queue

To send a message to the queue, you need to get the queue instance and call the `push()` method. Typically, with Yii Framework you'll get a `Queue` instance as a dependency of a service.

```php

final readonly class Foo {
    public function __construct(private QueueInterface $queue) {}

    public function bar(): void
    {
        $this->queue->push(new Message(
            // The first parameter is the message type used to resolve the handler which will process the message
            RemoteFileHandler::class,
            // The second parameter is the data that will be passed to the handler.
            // It should be serializable to JSON format
            ['url' => 'https://example.com/file-path.csv'],
        ));
    }
}
```

### 5. Handle queued messages

By default, Yii Framework uses [yiisoft/yii-console](https://github.com/yiisoft/yii-console) to run CLI commands. If you installed [yiisoft/app](https://github.com/yiisoft/app) or [yiisoft/app-api](https://github.com/yiisoft/app-api), you can run the queue worker with one of these two commands:

```bash
./yii queue:run # Handle all existing messages in the queue
./yii queue:listen [queueName] # Start a daemon listening for new messages permanently from the specified queue
./yii queue:listen-all [queueName [queueName2 [...]]] # Start a daemon listening for new messages permanently from all queues or specified list of queues (use with caution in production, recommended for dev only)
```

See [Console commands](docs/guide/en/console-commands.md) for more details.

> In case you're running the queue in synchronous mode (no adapter), `queue:listen` logs an info message and exits. The messages are processed immediately when pushed.

## Documentation

- [Guide](docs/guide/en/README.md)
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Queue is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
