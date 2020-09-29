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

Basic Usage
-----------

Each queue task consists of two parts:
1. Data payload. It is a class implementing `PayloadInterface`.
2. Payload handler. It is a callable called by a `WorkerInterface` which handles every queue message.

For example, if you need to download and save a file, the payload may look like the following:

```php
class DownloadJob implements Yiisoft\Yii\Queue\Payload\PayloadInterface
{
    public const NAME = 'file-download';

    public string $url;
    public string $fileName;
    
    public function __construct(string $url, string $fileName)
    {
        $this->url = $url;
        $this->fileName = $fileName;
    }
    
    public function getName(): string
    {
        return self::NAME;
    }

    public function getData(): array
    {
        return [
            'destinationFile' => $this->fileName,
            'url' => $this->url
        ];
    }

    public function getMeta(): array
    {
        return [];
    }
}
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
        $fileName = $downloadMessage->getPayloadData()['destinationFile'];
        $path = "$this->absolutePath/$fileName"; 
        file_put_contents($path, file_get_contents($downloadMessage->getPayloadData()['url']));
    }
}
```

The last thing we should do is to create configuration for the `Yiisoft\Yii\Queue\Worker\Worker`:
```php
$handlers = [DownloadJob::NAME => [new FileDownloader('/path/to/save/files'), 'handle']];
$worker = new \Yiisoft\Yii\Queue\Worker\Worker(
    $handlers, // Here it is
    $dispatcher,
    $logger,
    $injector,
    $container
);
```

Here's how to send a task into the queue:

```php
$queue->push(
    new DownloadJob('http://example.com/image.jpg', 'new-image-name.jpg')
);
```





To push a job into the queue that should run after 5 minutes:

```php
$queue->push(
    new class('http://example.com/image.jpg', '/tmp/image.jpg') extends DownloadJob 
    implements \Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface {

        public function getDelay(): int
        {
            return 5 * 60;
        }
    }
);
```

**Important:** Not every driver (such as synchronous driver) supports delayed execution.

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

```php
// Push a job into the queue and get a message ID.
$id = $queue->push(new SomeJob());

// Get status of the job
$status = $queue->status($id);

// Check whether the job is waiting for execution.
$status->isWaiting();

// Check whether a worker got the job from the queue and executes it.
$status->isReserved($id);

// Check whether a worker has executed the job.
$status->isDone($id);
```

For more details see [the guide](docs/guide/README.md).
