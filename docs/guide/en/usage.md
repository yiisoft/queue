# Usage basics

## Usage

For example, if you need to download and save a file, define a message class and push it:

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

```php
$message = new DownloadFileMessage(url: $url, destinationPath: $filename);
```

Here's how to push a message to the queue:

```php
$queue->push($message);
```

To push a message that should be processed after 5 minutes:

Delayed execution is implemented using the `DelayEnvelope`. The envelope wraps your message with delay information that adapters can use if they support delayed execution.

```php
use Yiisoft\Queue\Message\DelayEnvelope;

$delayedMessage = new DelayEnvelope($message, 5 * 60); // 5 minutes delay
$queue->push($delayedMessage);
```

**Important:** Adapters that support delaying will use the delay information from `DelayEnvelope` to schedule the message accordingly. Adapters that don't support delaying will **ignore the delay data** and process the message in the queue order.


## Queue handling

Most adapters can be consumed using [console commands](./console-commands.md) registered by `yiisoft/queue` in your application. For more details, check the respective adapter documentation.

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
$id = IdEnvelope::fromMessage($pushedMessage)->getId();

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

All data needed to handle a message must be included in the payload passed to `getPayload()`.
