# Message status

Yii Queue can report the status of a message by its ID.

The API surface is:

- `QueueInterface::status(string|int $id): MessageStatus`
- `AdapterInterface::status(string|int $id): MessageStatus`

Status tracking support depends on the adapter. If an adapter doesn't keep status history, calling `status()` with that ID will throw `InvalidArgumentException`.

## Getting a message ID

`QueueInterface::push()` returns a `MessageInterface`. When the adapter supports IDs, the returned message is typically wrapped into an `IdEnvelope`, which stores the ID in message metadata.

To read the ID:

```php
use Yiisoft\Queue\Message\IdEnvelope;

$pushedMessage = $queue->push($message);
$id = $pushedMessage->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? null;
```

If `$id` is `null`, the current adapter didn't provide an ID and you can't query a status.

The ID type (`string` or `int`) and how long it stays queryable are adapter-specific.

## Statuses

Statuses are represented by the `Yiisoft\Queue\MessageStatus` enum:

- `MessageStatus::WAITING`
  The message is in the queue and has not been picked up yet.

- `MessageStatus::RESERVED`
  A worker has taken the message and is handling it.

- `MessageStatus::DONE`
  The message has been handled.

In addition to enum cases, `MessageStatus` provides a string key via `MessageStatus::key()`:

```php
$statusKey = $status->key(); // "waiting", "reserved" or "done"
```

## Querying a status

```php
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\IdEnvelope;

$pushedMessage = $queue->push($message);
$id = $pushedMessage->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? null;

if ($id === null) {
    throw new \RuntimeException('The adapter did not provide a message ID, status tracking is unavailable.');
}

$status = $queue->status($id);

if ($status === MessageStatus::WAITING) {
    // The message is waiting to be handled.
}

if ($status === MessageStatus::RESERVED) {
    // A worker is currently handling the message.
}

if ($status === MessageStatus::DONE) {
    // The message has been handled.
}
```

## Errors and edge cases

- **Unknown ID**
  If an adapter can't find the message by ID, it must throw `InvalidArgumentException`.

- **Timing**
  `RESERVED` can be short-lived and difficult to observe: depending on the adapter, a message may move from `WAITING` to `RESERVED` and then to `DONE` quickly.

- **Failures / retries**
  Failures and retries are handled by the worker and middleware pipelines, described in [Errors and retryable messages](./error-handling.md).
  How failures affect the status is adapter-specific.
