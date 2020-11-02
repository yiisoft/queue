# Usage basics

## Configuration

You can configure it with a DI container in the following way:

```php
$eventDispatcher = $DIContainer->get(\Psr\EventDispatcher\EventDispatcherInterface::class);
$logger = $DIContainer->get(\Psr\Log\LoggerInterface::class);

$worker = $DIContainer->get(\Yiisoft\Yii\Queue\Worker\WorkerInterface::class);
$loop = $DIContainer->get(\Yiisoft\Yii\Queue\Cli\LoopInterface::class);
$driver = $DIContainer->get(\Yiisoft\Yii\Queue\Driver\DriverInterface::class);

$queue = new Queue(
    $driver,
    $eventDispatcher,
    $worker,
    $loop,
    $logger
);
```

See also the documentation for concrete drivers ([synchronous driver](driver-sync.md), 
[AMQP driver](https://github.com/yiisoft/yii-queue-amqp)) and [workers](worker.md)


## Usage

Each job sent to the queue should be defined as a separate class.
For example, if you need to download and save a file the class may look like the following:

```php
$data = [
    'url' => $url,
    'destinationFile' => $filename,
];
$message = new \Yiisoft\Yii\Queue\Message\Message('file-download', $data);
```

Here's how to send a task to the queue:

```php
$queue->push($message);
```

To push a job into the queue that should run after 5 minutes:

```php
$message->attachBehavior(new DelayBehavior(5 * 60));
$queue->push($message);
```

**Important:** Not every driver (such as synchronous driver) supports delayed execution.


## Queue handling

The exact way how a job is executed depends on the driver used. Most drivers can be run using
console commands, which the component registers in your application. For more details check the respective
driver documentation.


## Job status

```php
// Push a job into the queue and get a message ID.
$id = $queue->push(new SomeJob());

// Get job status.
$status = $queue->status($id);

// Check whether the job is waiting for execution.
$status->isWaiting();

// Check whether a worker got the job from the queue and executes it.
$status->isReserved($id);

// Check whether a worker has executed the job.
$status->isDone($id);
```


## Handling events

The queue triggers the following events:

| Event class        | Triggered                                                 |
|--------------------|-----------------------------------------------------------|
| BeforePush         | Before adding a job to queue using `Queue::push()` method.|
| AfterPush          | After adding a job to queue using `Queue::push()` method. |
| BeforeExecution    | Before executing a job.                                   |
| AfterExecution     | After successful job execution.                           |
| JobFailure         | On uncaught exception during the job execution.           |

## Logging events

In order to log events, please refer to `EventDispatcherInterface` implementation documentation
(i.e. [Yii Event Dispatcher](https://github.com/yiisoft/event-dispatcher#events-hierarchy)).

## Limitations

When using queues it is important to remember that tasks are put into and obtained from the queue in separate
processes. Therefore, avoid external dependencies when executing a task if you're not sure if they are available in
the environment where the worker does its job.

All the data to process the task should be provided with your payload `getData()` method.
