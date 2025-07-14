<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Queue</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/queue/v/stable.svg)](https://packagist.org/packages/yiisoft/queue)
[![Total Downloads](https://poser.pugx.org/yiisoft/queue/downloads.svg)](https://packagist.org/packages/yiisoft/queue)
[![Build status](https://github.com/yiisoft/queue/workflows/build/badge.svg)](https://github.com/yiisoft/queue/actions)
[![Code coverage](https://codecov.io/gh/yiisoft/queue/graph/badge.svg?token=NU2ST01B1U)](https://codecov.io/gh/yiisoft/queue)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fqueue%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/queue/master)
[![static analysis](https://github.com/yiisoft/queue/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/queue/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/queue/coverage.svg)](https://shepherd.dev/github/yiisoft/queue)

An extension for running tasks asynchronously via queues.

## Requirements

- PHP 8.1 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/queue
```

## Ready for Yii Config

If you are using [yiisoft/config](https://github.com/yiisoft/config), you'll find out this package has some defaults
in the [`common`](config/di.php) and [`params`](config/params.php) configurations saving your time. Things you should
change to start working with the queue:

- Optionally: define default `\Yiisoft\Queue\Adapter\AdapterInterface` implementation.
- And/or define channel-specific `AdapterInterface` implementations in the `channel` params key to be used
  with the [queue provider](#different-queue-channels).
- Define [message handlers](docs/guide/worker.md#handler-format) in the `handlers` params key to be used with the `QueueWorker`.
- Resolve other `\Yiisoft\Queue\Queue` dependencies (psr-compliant event dispatcher).

## Differences to yii2-queue

If you have experience with `yiisoft/yii2-queue`, you will find out that this package is similar.
Though, there are some key differences that are described in the "[migrating from yii2-queue](docs/guide/migrating-from-yii2-queue.md)"
article.

## General usage

Each queue task consists of two parts:

1. A message is a class implementing `MessageInterface`. For simple cases you can use the default implementation,
   `Yiisoft\Queue\Message\Message`. For more complex cases, you should implement the interface by your own.
2. A message handler is a callable called by a `Yiisoft\Queue\Worker\Worker`. The handler handles each queue message.

For example, if you need to download and save a file, your message creation may look like the following:
- Message handler as the first parameter
- Message data as the second parameter

```php
$data = [
    'url' => $url,
    'destinationFile' => $filename,
];
$message = new \Yiisoft\Queue\Message\Message(FileDownloader::class, $data);
```

Then you should push it to the queue:

```php
$queue->push($message);
```

Its handler may look like the following:

```php
class FileDownloader
{
    private string $absolutePath;

    public function __construct(string $absolutePath) 
    {
        $this->absolutePath = $absolutePath;
    }

    public function handle(\Yiisoft\Queue\Message\MessageInterface $downloadMessage): void
    {
        $fileName = $downloadMessage->getData()['destinationFile'];
        $path = "$this->absolutePath/$fileName"; 
        file_put_contents($path, file_get_contents($downloadMessage->getData()['url']));
    }
}
```

The last thing we should do is to create a configuration for the `Yiisoft\Queue\Worker\Worker`:

```php
$worker = new \Yiisoft\Queue\Worker\Worker(
    [],
    $logger,
    $injector,
    $container
);
```

There is a way to run all the messages that are already in the queue, and then exit:

```php
$queue->run(); // this will execute all the existing messages
$queue->run(10); // while this will execute only 10 messages as a maximum before exit
```

If you don't want your script to exit immediately, you can use the `listen` method:

```php
$queue->listen();
```

You can also check the status of a pushed message (the queue adapter you are using must support this feature):

```php
$queue->push($message);
$id = $message->getId();

// Get status of the job
$status = $queue->status($id);

// Check whether the job is waiting for execution.
$status->isWaiting();

// Check whether a worker got the job from the queue and executes it.
$status->isReserved();

// Check whether a worker has executed the job.
$status->isDone();
```

## Custom handler names
### Custom handler names

By default, when you push a message to the queue, the message handler name is the fully qualified class name of the handler.
This can be useful for most cases, but sometimes you may want to use a shorter name or arbitrary string as the handler name.
This can be useful when you want to reduce the amount of data being passed or when you communicate with external systems.

To use a custom handler name before message push, you can pass it as the first argument `Message` when creating it:
```php
new Message('handler-name', $data);
```

To use a custom handler name on message consumption, you should configure handler mapping for the `Worker` class:
```php
$worker = new \Yiisoft\Queue\Worker\Worker(
    ['handler-name' => FooHandler::class],
    $logger,
    $injector,
    $container
);
```

## Different queue channels

Often we need to push to different queue channels with an only application. There is the `QueueProviderInterface`
interface that provides different `Queue` objects creation for different channels. With implementation of this interface
channel-specific `Queue` creation is as simple as

```php
$queue = $provider->get('channel-name');
```

Out of the box, there are four implementations of the `QueueProviderInterface`:

- `AdapterFactoryQueueProvider`
- `PrototypeQueueProvider`
- `CompositeQueueProvider`

### `AdapterFactoryQueueProvider`

Provider based on the definition of channel-specific adapters. Definitions are passed in
the `$definitions` constructor parameter of the factory, where keys are channel names and values are definitions
for the [`Yiisoft\Factory\Factory`](https://github.com/yiisoft/factory). Below are some examples:

```php
use Yiisoft\Queue\Adapter\SynchronousAdapter;

[
    'channel1' => new SynchronousAdapter(),
    'channel2' => static fn(SynchronousAdapter $adapter) => $adapter->withChannel('channel2'),
    'channel3' => [
        'class' => SynchronousAdapter::class,
        '__constructor' => ['channel' => 'channel3'],
    ],
]
```

For more information about the definition formats available, see the [factory](https://github.com/yiisoft/factory) documentation.

### `PrototypeQueueProvider`

Queue provider that only changes the channel name of the base queue. It can be useful when your queues used the same
adapter.

> Warning: This strategy is not recommended as it does not give you any protection against typos and mistakes
> in channel names.

### `CompositeQueueProvider`

This provider allows you to combine multiple providers into one. It will try to get a queue from each provider in the
order they are passed to the constructor. The first queue found will be returned.

## Console execution

The exact way of task execution depends on the adapter used. Most adapters can be run using
console commands, which the component automatically registers in your application.

The following command obtains and executes tasks in a loop until the queue is empty:

```sh
yii queue:run
```

The following command launches a daemon which infinitely queries the queue:

```sh
yii queue:listen
```

See the documentation for more details about adapter specific console commands and their options.

The component can also track the status of a job which was pushed into queue.

For more details, see [the guide](docs/guide/en/README.md).

## Middleware pipelines

Any message pushed to a queue or consumed from it passes through two different middleware pipelines: one pipeline
on message push and another - on a message consume. The process is the same as for the HTTP request, but it is executed
twice for a queue message. That means you can add extra functionality on message pushing and consuming with configuration
of the two classes: `PushMiddlewareDispatcher` and `ConsumeMiddlewareDispatcher` respectively.

You can use any of these formats to define a middleware:

- A ready-to-use middleware object: `new FooMiddleware()`. It must implement `MiddlewarePushInterface`,
 `MiddlewareConsumeInterface` or `MiddlewareFailureInterface` depending on the place you use it.
- An array in the format of [yiisoft/definitions](https://github.com/yiisoft/definitions).
    **Only if you use yiisoft/definitions and yiisoft/di**.
- A `callable`: `fn() => // do stuff`, `$object->foo(...)`, etc. It will be executed through the
[yiisoft/injector](https://github.com/yiisoft/injector), so all the dependencies of your callable will be resolved.
- A string for your DI container to resolve the middleware, e.g. `FooMiddleware::class`

Middleware will be executed forwards in the same order they are defined. If you define it like the following:
`[$middleware1, $midleware2]`, the execution will look like this:

```mermaid
graph LR
    StartPush((Start)) --> PushMiddleware1[$middleware1] --> PushMiddleware2[$middleware2] --> Push(Push to a queue)
    -.-> PushMiddleware2[$middleware2] -.-> PushMiddleware1[$middleware1]
    PushMiddleware1[$middleware1] -.-> EndPush((End))
    

    StartConsume((Start)) --> ConsumeMiddleware1[$middleware1] --> ConsumeMiddleware2[$middleware2] --> Consume(Consume / handle)
    -.-> ConsumeMiddleware2[$middleware2] -.-> ConsumeMiddleware1[$middleware1]
    ConsumeMiddleware1[$middleware1] -.-> EndConsume((End))
```

### Push a pipeline

When you push a message, you can use middlewares to modify both message and queue adapter.
With message modification you can add extra data, obfuscate data, collect metrics, etc.  
With queue adapter modification you can redirect the message to another queue, delay message consuming, and so on.

To use this feature, you have to create a middleware class, which implements `MiddlewarePushInterface`, and
return a modified `PushRequest` object from the `processPush` method:

```php
return $pushRequest->withMessage($newMessage)->withAdapter($newAdapter);
```

With push middlewares you can define an adapter object at the runtime, not in the `Queue` constructor.
There is a restriction: by the time all middlewares are executed in the forward order, the adapter must be specified
in the `PushRequest` object. You will get a `AdapterNotConfiguredException`, if it isn't.

You have three places to define push middlewares:

1. `PushMiddlewareDispatcher`. You can pass it either to the constructor, or to the `withMiddlewares()` method, which  
creates a completely new dispatcher object with only those middlewares, which are passed as arguments.
If you use [yiisoft/config](yiisoft/config), you can add middleware to the `middlewares-push` key of the
`yiisoft/queue` array in the `params`.
2. Pass middlewares to either `Queue::withMiddlewares()` or `Queue::withMiddlewaresAdded()` methods. The difference is
that the former will completely replace an existing middleware stack, while the latter will add passed middlewares to
the end of the existing stack. These middlewares will be executed after the common ones, passed directly to the
`PushMiddlewareDispatcher`. It's useful when defining a queue channel. Both methods return a new instance of the `Queue`
class.
3. Put middlewares into the `Queue::push()` method like this: `$queue->push($message, ...$middlewares)`. These
middlewares have the lowest priority and will be executed after those which are in the `PushMiddlewareDispatcher` and
the ones passed to the `Queue::withMiddlewares()` and `Queue::withMiddlewaresAdded()` and only for the message passed
along with them.

### Consume pipeline

You can set a middleware pipeline for a message when it will be consumed from a queue server. This is useful to collect metrics, modify message data, etc. In a pair with a Push middleware you can deduplicate messages in the queue, calculate time from push to consume, handle errors (push to a queue again, redirect failed message to another queue, send a notification, etc.). Except push pipeline, you have only one place to define the middleware stack: in the `ConsumeMiddlewareDispatcher`, either in the constructor, or in the `withMiddlewares()` method. If you use [yiisoft/config](yiisoft/config), you can add middleware to the `middlewares-consume` key of the `yiisoft/queue` array in the `params`.

### Error handling pipeline

Often when some job is failing, we want to retry its execution a couple more times or redirect it to another queue channel. This can be done in `yiisoft/queue` with a Failure middleware pipeline. They are triggered each time message processing via the Consume middleware pipeline is interrupted with any `Throwable`. The key differences from the previous two pipelines:

- You should set up the middleware pipeline separately for each queue channel. That means, the format should be `['channel-name' => [FooMiddleware::class]]` instead of `[FooMiddleware::class]`, like for the other two pipelines. There is also a default key, which will be used for those channels without their own one: `FailureMiddlewareDispatcher::DEFAULT_PIPELINE`.
- The last middleware will throw the exception, which will come with the `FailureHandlingRequest` object. If you don't want the exception to be thrown, your middlewares should `return` a request without calling `$handler->handleFailure()`.

You can declare error handling a middleware pipeline in the `FailureMiddlewareDispatcher`, either in the constructor, or in the `withMiddlewares()` method. If you use [yiisoft/config](yiisoft/config), you can add middleware to the `middlewares-fail` key of the `yiisoft/queue` array in the `params`.

See [error handling docs](docs/guide/error-handling.md) for details.

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
