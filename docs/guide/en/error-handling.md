# Error handling on message processing

Often when message handling fails, we want to retry the message a couple more times or redirect it to another queue. In `yiisoft/queue` this is handled by the failure handling middleware pipeline.

## When failure handling is triggered

Failure handling is triggered when message processing throws a `Throwable` (exception, fatal error and so on: see [PHP documentation](https://www.php.net/manual/en/class.throwable.php) for details).

That means PHP notices/warnings are handled according to your PHP runtime configuration: they do not trigger failure handling unless they are converted into an exception (for example, via an error handler).

In practice it means:

- The worker runs message processing in `Yiisoft\Queue\Worker\Worker::process()`.
- Your message handler is invoked through the [consume middleware pipeline](middleware-pipelines.md#consume-pipeline).
- If any `Throwable` escapes that pipeline, the worker switches to the failure handling pipeline.

## What “handled failure” means

A failure is considered handled if the failure pipeline returns a `FailureHandlingRequest` without throwing.
In practice, built-in middlewares handle failures by re-queueing the message (same or different queue), optionally with a delay, and returning the updated request.

## Configuration

Here below is configuration via [yiisoft/config](https://github.com/yiisoft/config) (see also [Configuration with yiisoft/config](configuration-with-config.md)). If you don't use it, you should add a middleware definition list (in the `middlewares-fail` key here) to the `FailureMiddlewareDispatcher` [yourself](configuration-manual.md). You can define different failure handling pipelines for each queue name (see [Queue names](queue-names.md)). The example below defines two different failure handling pipelines:

```php
'yiisoft/queue' => [
    'middlewares-fail' => [
        FailureMiddlewareDispatcher::DEFAULT_PIPELINE => [
            [
                'class' => SendAgainMiddleware::class,
                '__construct()' => ['id' => 'default-first-resend', 'queue' => null], 
            ],
            static fn (QueueFactoryInterface $factory) => new SendAgainMiddleware(
                id: 'default-second-resend', 
                queue: $factory->get('failed-messages'),
            ),
        ],
        
        'failed-messages' => [
            [
                'class' => ExponentialDelayMiddleware::class,
                '__construct()' => [
                    'id' => 'failed-messages',
                    'maxAttempts' => 30,
                    'delayInitial' => 5,
                    'delayMaximum' => 60,
                    'exponent' => 1.5,
                    'queue' => null,
                ], 
            ],
        ],
    ],
]
```

Here is the meaning of the keys:
- The `failed-messages` key couples the defined pipeline with the `failed-messages` queue name. 
- The `FailureMiddlewareDispatcher::DEFAULT_PIPELINE` key couples the defined pipeline with all queue names without an explicitly defined failure strategy pipeline.

Each middleware definition must be one of:
- A ready-to-use `MiddlewareFailureInterface` object like `new FooMiddleware()`.
- A valid definition for the [yiisoft/definitions](https://github.com/yiisoft/definitions). It must describe an object, implementing the `MiddlewareFailureInterface`.
- An [extended callable definition](callable-definitions-extended.md).
- An id string for your DI container to resolve a middleware, e.g. `FooMiddleware::class`.

In the example above failures will be handled this way (look the concrete middleware description below):

1. For the first time message will be resent to the same queue immediately.
2. If it fails again, it will be resent to the queue named `failed-messages`.
3. From now on it will be resent to the same queue (`failed-messages`) up to 30 times with a delay from 5 to 60 seconds, increased 1.5 times each time the message fails again.
4. If the message handler throw an exception one more (33rd) time, the exception will not be caught.

Failures of messages that arrived in the `failed-messages` queue directly (bypassing the error-handling pipeline) will only be handled by the 3rd and the 4th points of this list.

## Default failure handling strategies
 
 Let's see the built-in defaults.
 
 ### SendAgainMiddleware
 
 This strategy simply resends the given message to a queue. Let's see the constructor parameters through which it's configured:

 - `id` - A unique string. Allows to use this strategy more than once for the same message, just like in example above.
 - `maxAttempts` - Maximum attempts count for this strategy with the given $id before it will give up.
 - `queue` - The strategy will send the message to the given queue when it's not `null`. That means you can use this strategy to push a message not to the same queue it came from. When the `queue` parameter is set to `null`, a message will be sent to the same queue it came from.

 State tracking:

 - Uses `FailureEnvelope` metadata (`failure-meta`) to store the per-middleware attempt counter.
 - The counter key is `failure-strategy-resend-attempts-{id}`.
 
 ### ExponentialDelayMiddleware
 
 This strategy does the same thing as the `SendAgainMiddleware` with a single difference: it resends a message with an exponentially increasing delay. The delay **must** be implemented by the used `AdapterInterface` implementation.

It's configured via constructor parameters, too. Here they are:

- `id` - A unique string that allows to use this strategy more than once for the same message, just like in example above.
- `maxAttempts` - Maximum attempts count for this strategy with the given $id before it will give up.
 - `delayInitial` - The initial delay that will be applied to a message for the first time. It must be a positive float. 
 - `delayMaximum` - The maximum delay which can be applied to a single message. Must be above the `delayInitial`.
 - `exponent` - Message handling delay will be multiplied by exponent each time it fails.
 - `queue` - The strategy will send the message to the given queue when it's not `null`. That means you can use this strategy to push a message not to the same queue it came from. When the `queue` parameter is set to `null`, a message will be sent to the same queue it came from.

 Requirements:

 - Requires a `DelayMiddlewareInterface` implementation and an adapter that supports delayed delivery.

 State tracking:

 - Uses `FailureEnvelope` metadata (`failure-meta`) to store attempts and the previous delay.
 - The per-middleware keys are:

   - `failure-strategy-exponential-delay-attempts-{id}`
   - `failure-strategy-exponential-delay-delay-{id}`

> For detailed internals of the failure handling pipeline, built-in components, and custom middleware implementation, see [Error handling internals](error-handling-advanced.md).
