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

A framework-agnostic PHP queue library for running tasks asynchronously, best used with Yii3 applications.

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

### 2. Prepare a message and handler

Define a message class for the work to be done — a simple value object with typed properties:

```php
use Yiisoft\Queue\Message\Message;

final class DownloadFileMessage extends Message
{
    public const TYPE = 'download-file';

    public function __construct(
        public readonly string $url,
        public readonly string $destinationPath,
    ) {}

    public static function fromPayload(string $type, bool|int|float|string|array|null $payload): static
    {
        if ($type !== self::TYPE) {
            throw new \InvalidArgumentException("Expected type \"" . self::TYPE . "\", got \"$type\".");
        }
        if (!is_array($payload)
            || !is_string($payload['url'] ?? null)
            || !is_string($payload['destinationPath'] ?? null)
        ) {
            throw new \InvalidArgumentException('Invalid data for ' . self::class . '.');
        }
        return new self($payload['url'], $payload['destinationPath']);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        return ['url' => $this->url, 'destinationPath' => $this->destinationPath];
    }
}
```

Then create a handler that processes it:

```php
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageHandlerInterface;

final readonly class RemoteFileHandler implements MessageHandlerInterface
{
    public function __construct(
        private FileDownloader $downloader,
        private FileProcessor $processor,
    ) {}

    public function handle(MessageInterface $message): void
    {
        assert($message instanceof DownloadFileMessage);
        $localPath = $this->downloader->download($message->url, $message->destinationPath);
        $this->processor->process($localPath);
    }
}
```

### 3. Configure the queue

#### Configuration with [yiisoft/config](https://github.com/yiisoft/config)

**If you use [yiisoft/app](https://github.com/yiisoft/app) or [yiisoft/app-api](https://github.com/yiisoft/app-api)**

Add queue configuration to your application `$params` config. In [yiisoft/app](https://github.com/yiisoft/app)/[yiisoft/app-api](https://github.com/yiisoft/app-api) templates it's typically the `config/params.php` file.
_If your project structure differs, put it into any params config file that is loaded by [yiisoft/config](https://github.com/yiisoft/config)._

Minimal configuration example:

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            DownloadFileMessage::TYPE => RemoteFileHandler::class,
        ],
    ],
];
```

[Advanced configuration with `yiisoft/config`](docs/guide/en/configuration-with-config.md)

#### Manual configuration

For setting up all classes manually, see the [Manual configuration](docs/guide/en/configuration-manual.md) guide.

### 4. Send (produce/push) a message to a queue

To send a message to the queue, get the queue instance and call `push()`. Typically the queue is injected as a dependency:

```php
final readonly class Foo
{
    public function __construct(private QueueInterface $queue) {}

    public function bar(): void
    {
        $this->queue->push(new DownloadFileMessage(
            url: 'https://example.com/file-path.csv',
            destinationPath: '/tmp/file-path.csv',
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

## Versioning

This package follows [semantic versioning](https://semver.org/).

The `/stubs` directory is intended for testing purposes only and must not be used in production code.
Any changes there, including breaking ones, are always released as a patch version.

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
