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

Documentation for drivers([synchronous driver](driver-sync.md), AMQP driver), loops, workers


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
        file_put_contents($this->file, file_get_contents($this->url));
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

The component provides the `LogBehavior` to log Queue events using
[Yii's built-in Logger](http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html).

To enable it, simply configure the queue component as follows:

```php
return [
    'components' => [
        'queue' => [
            'class' => \Yiisoft\Yii\Queue\redis\Queue::class,
            'as log' => \Yiisoft\Yii\Queue\LogBehavior::class
        ],
    ],
];
```

Limitations
-----------

When using queues it's important to remember that tasks are put into and obtained from the queue in separate
processes. Therefore avoid external dependencies when executing a task if you're not sure if they are available in
the environment where the worker does its job.

All the data to process the task should be put into properties of your job object and be sent into the queue along with it.

If you need to process an `ActiveRecord` then send its ID instead of the object itself. When processing you have to extract
it from DB.

For example:

```php
Yii::$app->queue->push(new SomeJob([
    'userId' => Yii::$app->user->id,
    'bookId' => $book->id,
    'someUrl' => Url::to(['controller/action']),
]));
```

Task class:

```php
class SomeJob extends BaseObject implements \Yiisoft\Yii\Queue\JobInterface
{
    public $userId;
    public $bookId;
    public $someUrl;

    public function execute($queue)
    {
        $user = User::findOne($this->userId);
        $book = Book::findOne($this->bookId);
        //...
    }
}
```
