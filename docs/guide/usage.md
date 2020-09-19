Usage basics
============


Configuration
-------------

In order to use the extension you can configure it with DI container like the following:

```php
$eventDisptacher = $DIcontainer->get(\Psr\EventDispatcher\EventDispatcherInterface::class);
$logger = $DIcontainer->get(\Psr\Log\LoggerInterface::class);

$worker = $DIcontainer->get(\Yiisoft\Yii\Queue\Worker\WorkerInterface::class);
$loop = $DIcontainer->get(\Yiisoft\Yii\Queue\Cli\LoopInterface::class);
$driver = $DIcontainer->get(\Yiisoft\Yii\Queue\Driver\DriverInterface::class);

$queue = new Queue(
    $driver,
    $eventDisptacher,
    $worker,
    $loop,
    $logger
);
```

Documentation for drivers([synchronous driver](driver-sync.md), [AMQP driver](https://github.com/yiisoft/yii-queue-amqp)), 
[loops](loops.md), [workers](worker.md)


Usage
-----

Each task which is sent to the queue should be defined as a separate class.
For example, if you need to download and save a file the class may look like the following:

```php
class DownloadJob implements Yiisoft\Yii\Queue\Payload\PayloadInterface
{
    public $url;
    public $file;
    
    public function __construct(string $url, string $file)
    {
        $this->url = $url;
        $this->file = $file;
    }
    
    public function getName(): string
    {
        return 'earlyDefinedQueueHandlerName';
    }

    public function getData()
    {
        return function () {
            file_put_contents($this->file, file_get_contents($this->url));
        };
    }

    public function getMeta(): array
    {
        return [];
    }
}
```

Here's how to send a task to the queue:

```php
$queue->push(
    new DownloadJob('http://example.com/image.jpg', '/tmp/image.jpg')
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

**Important:** Not all drivers(i.e. synchronous driver) support delayed running.


Queue handling
--------------

The exact way how a task is executed depends on the driver being used. Most drivers can be run using
console commands, which the component registers in your application. For more details check the respective
driver documentation.


Job status
----------

```php
// Push a job into the queue and get a message ID.
$id = $queue->push(new SomeJob());

//Get status of job
$status = $queue->status($id);

// Check whether the job is waiting for execution.
$status->isWaiting();

// Check whether a worker got the job from the queue and executes it.
$status->isReserved($id);

// Check whether a worker has executed the job.
$status->isDone($id);
```


Handling events
---------------

The queue triggers the following events:

| Event class        | Triggered                                                 |
|--------------------|-----------------------------------------------------------|
| BeforePush         | before adding a job to queue using `Queue::push()` method |
| AfterPush          | after adding a job to queue using `Queue::push()` method  |
| BeforeExecution    | before executing a job                                    |
| AfterExecution     | after successful job execution                            |
| JobFailure         | on uncaught exception during the job execution            |

Logging events
--------------

In order to log events, please refer to documentation of implementation of EventDispatcherInterface
(i.e. [Yii Event Dispatcher](https://github.com/yiisoft/event-dispatcher#events-hierarchy))

Limitations
-----------

When using queues it's important to remember that tasks are put into and obtained from the queue in separate
processes. Therefore avoid external dependencies when executing a task if you're not sure if they are available in
the environment where the worker does its job.

All the data to process the task should be provided with data of your payload
