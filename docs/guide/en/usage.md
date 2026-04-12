# Usage basics

## Usage

For example, if you need to download and save a file, you can create a message like this:

```php
$message = new \Yiisoft\Queue\Message\Message(
    RemoteFileHandler::class,
    ['url' => $url, 'destinationFile' => $filename]
);
```

Here's how to push a message to the queue:

```php
$queue->push($message);
```

To push a message that should be processed after 5 minutes:

Delayed execution is implemented via a push middleware.
The middleware must implement `\Yiisoft\Queue\Middleware\Push\Implementation\DelayMiddlewareInterface` and be provided by the adapter package you use.
For example, the official AMQP adapter supports delays: <https://github.com/yiisoft/queue-amqp>

```php
$delayMiddleware = $container->get(\Yiisoft\Queue\Middleware\Push\Implementation\DelayMiddlewareInterface::class);
$queue->push($message, $delayMiddleware->withDelay(5 * 60));
```

**Important:** Not every adapter (such as synchronous adapter) supports delayed execution.


## Queue handling

Most adapters can be run using [console commands](./console-commands.md) registered by this component in your application. For more details, check the respective adapter documentation.

If you configured multiple [queue names](./queue-names.md), you can choose which queue to consume with console commands:

```sh
yii queue:listen [queueName]
yii queue:run [queueName1 [queueName2 [...]]]
yii queue:listen-all [queueName1 [queueName2 [...]]]
```


## Message status

```php
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\IdEnvelope;

$pushedMessage = $queue->push($message);
$id = $pushedMessage->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? null;

if ($id === null) {
    throw new \RuntimeException('The adapter did not provide a message ID, status tracking is unavailable.');
}

$status = $queue->status($id);

// Check whether the message is waiting to be handled.
$status === MessageStatus::WAITING;

// Check whether a worker has picked up the message and is handling it.
$status === MessageStatus::RESERVED;

// Check whether the message has been processed.
$status === MessageStatus::DONE;
```

For details and edge cases, see [Message status](message-status.md).

## Limitations

Messages are pushed in one process and consumed in another. Avoid relying on in-process state (open connections, cached objects, etc.) that may not be available in the worker process.

All data needed to handle a message must be included in the payload passed to `getData()`.
