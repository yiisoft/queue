# Error handling internals

This document covers advanced internals of the failure handling pipeline, built-in components, and custom middleware implementation.

## Failure handling pipeline overview (step-by-step)

1. A message is processed via the consume pipeline

    The worker builds a `Yiisoft\Queue\Middleware\Consume\ConsumeRequest` and dispatches it through `ConsumeMiddlewareDispatcher`. The final consume handler invokes the resolved message handler.

2. A `Throwable` is caught by the worker

    If any middleware or the message handler throws, `Worker::process()` catches it.

3. Failure context is wrapped into a request object

    The worker creates a `Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest` containing:

    - the message
    - the caught exception
    - the queue instance

4. A failure pipeline is selected by queue name

    `FailureMiddlewareDispatcher::dispatch()` selects which pipeline to run:

    - It tries to use the pipeline configured for the current queue name.
    - If there is no pipeline for that queue name (or it is empty), it falls back to `FailureMiddlewareDispatcher::DEFAULT_PIPELINE`.

5. Failure middlewares are executed

    The dispatcher builds a lazy middleware stack (`MiddlewareFailureStack`) and invokes it.

    Each failure middleware implements `MiddlewareFailureInterface`:

    - It receives the `FailureHandlingRequest` and a continuation handler.
    - It may handle the failure by re-queueing the message (same or different queue), optionally with a delay.
    - If it decides not to handle the failure, it calls `$handler->handleFailure($request)` to continue the pipeline.

6. If nothing handles the failure, the exception is rethrown

    The failure pipeline ends with `FailureFinalHandler`, which throws `$request->getException()`.

7. The worker wraps and rethrows

    If the failure pipeline itself ends with an exception, `Worker::process()` wraps it into `Yiisoft\Queue\Exception\MessageFailureException` (including message id from `IdEnvelope` metadata when available) and throws it.

## Built-in failure handling components

This package ships the following built-in failure handling components.

### FailureEnvelope

Class: `Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope`

Behavior:

- An envelope that stores failure-related metadata under the `failure-meta` key.
- Built-in failure middlewares use it to persist retry counters / delay parameters across retries.

### FailureFinalHandler

Class: `Yiisoft\Queue\Middleware\FailureHandling\FailureFinalHandler`

Behavior:

- Terminal failure handler.
- Throws the exception from the request when the failure pipeline does not handle the failure.

### MessageFailureException

Class: `Yiisoft\Queue\Exception\MessageFailureException`

Behavior:

- Thrown by the worker when failure handling does not resolve the issue.
- Wraps the original exception and includes the queue message id (if available) in the exception message.

## How to create a custom Failure Middleware

All you need is to implement the `MiddlewareFailureInterface` and add your implementation definition to the [configuration](error-handling.md#configuration).
This interface has the only method `processFailure` with these parameters:
- [`FailureHandlingRequest $request`](../../../src/Middleware/FailureHandling/FailureHandlingRequest.php) - a request for a message handling. It consists of
    - a [message](../../../src/Message/MessageInterface.php)
    - a `Throwable $exception` object thrown on the `request` handling
    - a queue the message came from
- `MessageFailureHandlerInterface $handler` - failure strategy pipeline continuation. Your Middleware should call `$handler->handleFailure($request)` when the middleware itself should not interrupt failure pipeline execution.

> Note: your strategy have to check by its own if it should be applied. Look into [`SendAgainMiddleware::suits()`](../../../src/Middleware/FailureHandling/Implementation/SendAgainMiddleware.php#L54) for an example.
