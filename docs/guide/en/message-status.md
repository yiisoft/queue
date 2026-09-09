# Message status

Yii Queue can report the status of a message by its ID.

Status is a producer capability exposed by `getStatus(): QueueProducerStatusInterface`. Call `status(string|int $id)` on the returned capability, not on the producer itself. For named queues, `QueueProducerStatusProviderInterface` is keyed by the logical queue name; its `getStatus($queueName)` method returns the capability for that queue.

`AsyncQueueProducer` uses adapter-backed status tracking by default. `SyncQueueProducer` uses a status capability that returns `MessageStatus::NOT_FOUND` by default; pass a custom capability when synchronous status tracking is available. For an asynchronous adapter that doesn't support status tracking or can't find the message by ID, it also returns `MessageStatus::NOT_FOUND`.

## Getting a message ID

The examples below assume `$queue` is the configured producer for the default queue and `$message` is the message to push. A producer's `push()` method returns a `MessageInterface`. When the adapter supports IDs, the adapter-returned message is typically wrapped into an `IdEnvelope`, which stores the ID in message metadata.

To read the ID:

```php
use Yiisoft\Queue\Message\IdEnvelope;

$pushedMessage = $queue->push($message);
$id = IdEnvelope::fromMessage($pushedMessage)->getId();
```

If `$id` is `null`, the current adapter didn't provide an ID and you can't query a status.

The ID type (`string` or `int`) and how long it stays queryable are adapter-specific.

## Statuses

Statuses are represented by the `Yiisoft\Queue\MessageStatus` enum:

- `MessageStatus::NOT_FOUND`
  The message is not known to the queue, or the adapter doesn't support status tracking.

- `MessageStatus::WAITING`
  The message is in the queue and has not been picked up yet.

- `MessageStatus::RESERVED`
  A worker has taken the message and is handling it.

- `MessageStatus::DONE`
  The message has been handled.

In addition to enum cases, `MessageStatus` provides a string key via `MessageStatus::key()`:

```php
$statusKey = $status->key(); // "not-found", "waiting", "reserved" or "done"
```

## Querying a status

```php
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\IdEnvelope;

$pushedMessage = $queue->push($message);
$id = IdEnvelope::fromMessage($pushedMessage)->getId();

if ($id === null) {
    throw new \RuntimeException('The adapter did not provide a message ID, status tracking is unavailable.');
}

$status = $queue->getStatus()->status($id);

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

## Named queues

Use the status provider with the same logical queue name used by the producer. The provider returns the configured producer's status capability:

```php
use Yiisoft\Queue\Provider\QueueProducerStatusProviderInterface;

// `$statusProvider` is the configured QueueProducerStatusProviderInterface.
$status = $statusProvider->getStatus('emails')->status($id);
```

`hasStatus($queueName)` can be used to check whether a producer status capability is configured, and `getStatusQueueNames()` lists the available queue keys. For an unknown queue, or a queue without a producer, `getStatus($queueName)` throws `QueueNotFoundException`; it does not return `MessageStatus::NOT_FOUND`. `NOT_FOUND` is the result for a known queue when the message ID is unknown or status tracking is unsupported.

## Edge cases

- **Unknown ID or unsupported tracking**
  If an adapter doesn't support status tracking or can't find the message by ID, it returns `MessageStatus::NOT_FOUND`.

- **Timing**
  `RESERVED` can be short-lived and difficult to observe: depending on the adapter, a message may move from `WAITING` to `RESERVED` and then to `DONE` quickly.

- **Failures / retries**
  Failures and retries are handled by the worker and middleware pipelines, described in [Errors and retryable messages](./error-handling.md).
  How failures affect the status is adapter-specific.
