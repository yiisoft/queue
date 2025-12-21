# Failure handling pipeline

`yiisoft/queue` can deal with errors that happen while a worker is processing a message. This guide explains what exactly happens when something goes wrong, and when you should rely on the built-in failure handling vs. when the exception will be bubbled up.

## When failure handling is triggered

Failure handling is triggered only when message processing throws a `Throwable`.

In practice it means:

- The worker runs message processing in `Yiisoft\Queue\Worker\Worker::process()`.
- Your message handler is executed through the consume middleware pipeline.
- If any `Throwable` escapes that pipeline, the worker switches to the failure handling pipeline.

## Pipeline overview (step-by-step)

1. A message is processed via the consume pipeline

   The worker builds a `Yiisoft\Queue\Middleware\Consume\ConsumeRequest` and dispatches it through `ConsumeMiddlewareDispatcher`. The final consume handler invokes the resolved message handler.

2. A `Throwable` is caught by the worker

   If any middleware or the message handler throws, `Worker::process()` catches it.

3. Failure context is wrapped into a request object

   The worker creates a `Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest` containing:

   - the message
   - the caught exception
   - the queue instance (including its channel)

4. A failure pipeline is selected by queue channel

   `FailureMiddlewareDispatcher::dispatch()` selects which pipeline to run:

   - It tries to use the pipeline configured for the current queue channel.
   - If there is no pipeline for that channel (or it is empty), it falls back to `FailureMiddlewareDispatcher::DEFAULT_PIPELINE`.

5. Failure middlewares are executed

   The dispatcher builds a lazy middleware stack (`MiddlewareFailureStack`) and invokes it.

   Each failure middleware implements `MiddlewareFailureInterface`:

   - It receives the `FailureHandlingRequest` and a continuation handler.
   - It may “handle” the failure by producing a new request (for example, by pushing a retry message to some queue and returning `withMessage(...)` / `withQueue(...)`).
   - If it decides not to handle the failure, it calls `$handler->handleFailure($request)` to continue the pipeline.

6. If nothing handles the failure, the exception is rethrown

   The failure pipeline ends with `FailureFinalHandler`, which throws `$request->getException()`.

7. The worker wraps and rethrows

   If the failure pipeline itself ends with an exception, `Worker::process()` wraps it into `Yiisoft\Queue\Exception\JobFailureException` (including message id from `IdEnvelope` metadata when available) and throws it.

## What “handled failure” means

A failure is considered “handled” if the failure pipeline returns a `FailureHandlingRequest` without throwing.

In practice, built-in middlewares “handle” failures by re-queueing the message (same or different queue/channel), optionally with a delay, and returning the updated request.

## Built-in failure handling components

This package ships the following built-in failure handling components.

### SendAgainMiddleware

Class: `Yiisoft\Queue\Middleware\FailureHandling\Implementation\SendAgainMiddleware`

Behavior:

- Resends the message to a queue immediately.
- If `targetQueue` is `null`, it resends to the original queue.
- It stops applying itself after `maxAttempts` attempts.

State tracking:

- Uses `FailureEnvelope` metadata (`failure-meta`) to store the per-middleware attempt counter.
- The counter key is `failure-strategy-resend-attempts-{id}`.

### ExponentialDelayMiddleware

Class: `Yiisoft\Queue\Middleware\FailureHandling\Implementation\ExponentialDelayMiddleware`

Behavior:

- Resends the message with an exponentially increasing delay.
- Requires a `DelayMiddlewareInterface` implementation and an adapter that supports delayed delivery.
- Can resend to an explicitly provided queue or to the original queue.
- It stops applying itself after `maxAttempts` attempts.

State tracking:

- Uses `FailureEnvelope` metadata (`failure-meta`) to store attempts and the previous delay.
- The per-middleware keys are:

  - `failure-strategy-exponential-delay-attempts-{id}`
  - `failure-strategy-exponential-delay-delay-{id}`

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

### JobFailureException

Class: `Yiisoft\Queue\Exception\JobFailureException`

Behavior:

- Thrown by the worker when failure handling does not resolve the issue.
- Wraps the original exception and includes the queue message id (if available) in the exception message.
