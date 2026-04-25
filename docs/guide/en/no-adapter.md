Queue Without an Adapter (Synchronous Mode)
============================================

When no adapter is configured, the queue works in **synchronous mode**: each message is processed immediately inside the `push()` call, in the same process and request. No background worker is needed.

## When it is useful

- **Development and debugging**: push and handle messages without running a worker or setting up a message broker.
- **Unit and integration tests**: avoid external dependencies and verify handler logic directly.

## How it works

```php
use Yiisoft\Queue\Queue;

$queue = new Queue(
    worker: $worker,
    loop: $loop,
    logger: $logger,
    pushMiddlewareDispatcher: $pushMiddlewareDispatcher,
    // no adapter — synchronous mode
);

$queue->push($message);
// Handler is called immediately, before push() returns.
```

When `push()` is called:

1. All **push middlewares** run (ID assignment, custom enrichment, etc.).
2. Because there is no adapter, the message is **not stored** anywhere.
3. The worker processes the message **synchronously** right away.

## Constructor warning

To make the missing adapter visible, the `Queue` constructor logs a `warning` when no adapter is given:

```
Queue "default" has no adapter configured. Messages will be processed synchronously on push.
Add an adapter for asynchronous processing in production.
```

This warning appears once at construction time. It is your signal to wire a real adapter before deploying to production.

## Methods unavailable without an adapter

`run()`, `listen()`, and `status()` all require an adapter. They throw `BadMethodCallException` when called on a queue that has no adapter configured:

```php
$queue->run();     // BadMethodCallException
$queue->listen();  // BadMethodCallException
$queue->status(1); // BadMethodCallException
```

## Production use

**Always configure an adapter in production.** Without one:

- Messages are lost if the handler throws an exception (no retry).
- There is no backpressure or worker concurrency.
- The entire duration of message handling blocks the web request.

See [adapter list](adapter-list.md) for available production-ready adapters.
