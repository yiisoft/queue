<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Queue Extension</h1>
    <br>
</p>

An extension for running tasks asynchronously via queues.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-queue/v/stable.svg)](https://packagist.org/packages/yiisoft/yii-queue)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-queue/downloads.svg)](https://packagist.org/packages/yiisoft/yii-queue)
[![Build status](https://github.com/yiisoft/yii-queue/workflows/build/badge.svg)](https://github.com/yiisoft/yii-queue/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/yii-queue/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-queue/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/yii-queue/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-queue/?branch=master)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yiisoft/yii-queue
```

or add

```
"yiisoft/yii-queue": "~3.0"
```

to the `require` section of your `composer.json` file.

Differences to yii2-queue
-------------------------
If you have experience with `yiisoft/yii2-queue`, you will find out that this package is almost the same. But there some key differences which are described in the "[migrating from yii2-queue](docs/guide/migrating-from-yii2-queue.md)" article.

Basic Usage
-----------

Each queue task consists of two parts:
1. A message. It is a class implementing `MessageInterface`. For simple cases you can use the default implementation: `Yiisoft\Yii\Queue\Message\Message`. For more complex cases you should implement hte interface by your own.
2. Message handler. It is a callable called by a `Yiisoft\Yii\Queue\Worker\Worker` which handles every queue message.

For example, if you need to download and save a file, your message may look like the following:

```php
$data = [
    'url' => $url,
    'destinationFile' => $filename,
];
$message = new \Yiisoft\Yii\Queue\Message\Message('file-download', $data);
```

Then you should push it to the queue:
```php
$queue->push($message);
```

And its handler may look like the following:

```php
class FileDownloader
{
    private string $absolutePath;

    public function __construct(string $absolutePath) 
    {
        $this->absolutePath = $absolutePath;
    }

    public function handle(\Yiisoft\Yii\Queue\Message\MessageInterface $downloadMessage): void
    {
        $fileName = $downloadMessage->getData()['destinationFile'];
        $path = "$this->absolutePath/$fileName"; 
        file_put_contents($path, file_get_contents($downloadMessage->getData()['url']));
    }
}
```

The last thing we should do is to create configuration for the `Yiisoft\Yii\Queue\Worker\Worker`:
```php
$handlers = ['file-download' => [new FileDownloader('/path/to/save/files'), 'handle']];
$worker = new \Yiisoft\Yii\Queue\Worker\Worker(
    $handlers, // Here it is
    $dispatcher,
    $logger,
    $injector,
    $container
);
```

There is the way to run all the messages that are already in the queue, and then exit:
```php
$queue->run(); // this will execute all the existing messages
$queue->run(10); // while this will execute only 10 messages as a maximum before exit
```

If you don't want your script to exit immediately, you can use the `listen` method:
```php
$queue->listen();
```

You can also check the status of a pushed message (the queue driver you are using must support this feature):
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

Driver behaviors
----------------

Some of queue drivers support different behaviors like delayed execution and prioritirized queues.

**Important:** Not every driver supports all the behaviors. See concrete driver documentation to find out if it supports the behavior you want to use. Driver will throw a `BehaviorNotSupportedException` if it does not support some behaviors attached to the message you are trying to push to the queue.

### Delay behavior

To be sure message will be read from queue not earlier then 5 seconds since it will be pushed to the queue, you can use `DelayBehavior`:
```php
$message->attachBehavior(new DelayBehavior(5));
$queue->push($message);
```

Console execution
-----------------

The exact way a task is executed depends on the used driver. Most drivers can be run using
console commands, which the component automatically registers in your application.

This command obtains and executes tasks in a loop until the queue is empty:

```sh
yii queue/run
```

This command launches a daemon which infinitely queries the queue:

```sh
yii queue/listen
```

See the documentation for more details about driver specific console commands and their options.

The component also has the ability to track the status of a job which was pushed into queue.

For more details see [the guide](docs/guide/README.md).
