# Job status

Yii Queue can report a job status by its message ID.

The API surface is:

- `QueueInterface::status(string|int $id): JobStatus`
- `AdapterInterface::status(string|int $id): JobStatus`

Status tracking support depends on the adapter. If an adapter doesn't store IDs or doesn't keep status history, you might not be able to use `status()` reliably.

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

Statuses are represented by the `Yiisoft\Queue\JobStatus` enum:

- `JobStatus::WAITING`
  The job exists in the queue and is waiting for execution.

- `JobStatus::RESERVED`
  A worker has taken the job for processing.

- `JobStatus::DONE`
  The job has been processed.

In addition to enum cases, `JobStatus` provides a string key via `JobStatus::key()`:

```php
$statusKey = $status->key(); // "waiting", "reserved" or "done"
```

## Querying a status

```php
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\IdEnvelope;

$pushedMessage = $queue->push($message);
$id = $pushedMessage->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? null;

if ($id === null) {
    throw new \RuntimeException('The adapter did not provide a message ID, status tracking is unavailable.');
}

$status = $queue->status($id);

if ($status === JobStatus::WAITING) {
    // The job is waiting for execution.
}

if ($status === JobStatus::RESERVED) {
    // A worker is processing the job right now.
}

if ($status === JobStatus::DONE) {
    // The job has been processed.
}
```

## Errors and edge cases

- **Unknown ID**
  If an adapter can't find the message by ID, it must throw `InvalidArgumentException`.

- **Timing**
  `RESERVED` can be transient: depending on the adapter, a job may move from `WAITING` to `RESERVED` and then to `DONE` quickly.

- **Failures / retries**
  Job failures and retries are handled by the worker and middleware pipelines and are described in [Errors and retryable jobs](./error-handling.md).
  How failures affect job status is adapter-specific.
