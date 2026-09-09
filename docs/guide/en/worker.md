# Worker

`Worker` processes messages and resolves their handlers. Before handler resolution, it runs the optional native worker middleware pipeline configured with `middlewares-worker`.

## Worker middleware

Worker middleware receives an immutable `WorkerRequest` and returns a `WorkerRequest`. Use it for processing-level instrumentation and events that must cover the complete worker operation. In particular, worker processing events belong here rather than in a producer or consumer proxy.

A worker middleware implements `WorkerMiddlewareInterface`:

```php
public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest
{
    $this->started($request->getQueueName(), $request->getMessage());
    return $handler->handleWorker($request);
}
```

The request carries the normalized queue name, message, and an optional retry target typed as `Closure(MessageInterface): MessageInterface`. Retry strategies call that closure with a message; they do not receive a producer interface.

## Starting workers

Resolve `Worker` and its dependencies (for example, through a DI container), and [define handlers](message-handler-advanced.md) for each message consumed by the worker.

Start a worker with console commands such as `queue:run`, `queue:listen`, and `queue:listen-all`. See [Console commands](console-commands.md) for details.

For production-grade process management with `systemd`, Supervisor, or cron, see [Running workers in production](process-managers.md).
