# Usage basics

## Configuration

You can configure it with a DI container in the following way:

```php
$logger = $DIContainer->get(\Psr\Log\LoggerInterface::class);

$worker = $DIContainer->get(\Yiisoft\Queue\Worker\WorkerInterface::class);
$loop = $DIContainer->get(\Yiisoft\Queue\Cli\LoopInterface::class);
$adapter = $DIContainer->get(\Yiisoft\Queue\Adapter\AdapterInterface::class);

$queue = new Queue(
    $adapter,
    $worker,
    $loop,
    $logger
);
```

See also the documentation for concrete adapters ([synchronous adapter](adapter-sync.md), 
[AMQP adapter](https://github.com/yiisoft/queue-amqp)) and [workers](worker.md)


## Usage

Each job sent to the queue should be defined as a separate class.
For example, if you need to download and save a file the class may look like the following:

```php
$data = [
    'url' => $url,
    'destinationFile' => $filename,
];
$message = new \Yiisoft\Queue\Message\Message('file-download', $data);
```

Here's how to send a task to the queue:

```php
$queue->push($message);
```

To push a job into the queue that should run after 5 minutes:

```php
// TODO
```

**Important:** Not every adapter (such as synchronous adapter) supports delayed execution.


## Queue handling

The exact way how a job is executed depends on the adapter used. Most adapters can be run using
console commands, which the component registers in your application. For more details check the respective
adapter documentation.


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

## Limitations

When using queues it is important to remember that tasks are put into and obtained from the queue in separate
processes. Therefore, avoid external dependencies when executing a task if you're not sure if they are available in
the environment where the worker does its job.

All the data to process the task should be provided with your payload `getData()` method.
