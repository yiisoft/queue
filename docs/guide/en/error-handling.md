# Error handling on message processing

Often when some message handling is failing, we want to retry its execution a couple more times or redirect it to another queue channel. This can be done in [yiisoft/queue](https://github.com/yiisoft/queue) with _Failure Handling Middleware Pipeline_. It is triggered each time message processing via Consume Middleware Pipeline is interrupted with any `Throwable`. 

## Configuration

Here below is configuration via [yiisoft/config](https://github.com/yiisoft/config). If you don't use it, you should add a middleware definition list (in the `middlewares-fail` key here) to the `FailureMiddlewareDispatcher` by your own. You can define different failure handling pipelines for each queue channel. The example below defines two different failure handling pipelines:

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
- The `failed-messages` key couples the defined pipeline with the `failed-messages` queue channel. 
- The `FailureMiddlewareDispatcher::DEFAULT_PIPELINE` key couples the defined pipeline with all queue channels without an explicitly defined failure strategy pipeline.

Each middleware definition must be one of:
- A ready-to-use `MiddlewareFailureInterface` object like `new FooMiddleware()`.
- A valid definition for the [yiisoft/definitions](https://github.com/yiisoft/definitions). It must describe an object, implementing the `MiddlewareFailureInterface`.
- An [extended callable definition](callable-definitions-extended.md).
- An id string for your DI container to resolve a middleware, e.g. `FooMiddleware::class`.

In the example above failures will be handled this way (look the concrete middleware description below):

1. For the first time message will be resent to the same queue channel immediately.
2. If it fails again, it will be resent to the queue channel named `failed-messages`.
3. From now on it will be resent to the same queue channel (`failed-messages`) up to 30 times with a delay from 5 to 60 seconds, increased 1.5 times each time the message fails again.
4. If the message handler throw an exception one more (33rd) time, the exception will not be caught.

Failures of messages, which are initially sent to the `failed-messages` channel, will only be handled by the 3rd and the 4th points of this list.

## Default failure handling strategies

Let's see the built-in defaults.

### [SendAgainMiddleware](../../../src/Middleware/FailureHandling/Implementation/SendAgainMiddleware.php)

This strategy simply resends the given message to a queue. Let's see the constructor parameters through which it's configured:

- `id` - A unique string. Allows to use this strategy more than once for the same message, just like in example above.
- `maxAttempts` - Maximum attempts count for this strategy with the given $id before it will give up.
- `queue` - The strategy will send the message to the given queue when it's not `null`. That means you can use this strategy to push a message not to the same queue channel it came from. When the `queue` parameter is set to `null`, a message will be sent to the same channel it came from.

### [ExponentialDelayMiddleware](../../../src/Middleware/FailureHandling/Implementation/ExponentialDelayMiddleware.php)

This strategy does the same thing as the `SendAgainMiddleware` with a single difference: it resends a message with an exponentially increasing delay. The delay **must** be implemented by the used `AdapterInterface` implementation.

It's configured via constructor parameters, too. Here they are:

- `id` - A unique string allows to use this strategy more than once for the same message, just like in example above.
- `maxAttempts` - Maximum attempts count for this strategy with the given $id before it will give up.
- `delayInitial` - The initial delay that will be applied to a message for the first time. It must be a positive float. 
- `delayMaximum` - The maximum delay which can be applied to a single message. Must be above the `delayInitial`.
- `exponent` - Message handling delay will be multiplied by exponent each time it fails.
- `queue` - The strategy will send the message to the given queue when it's not `null`. That means you can use this strategy to push a message not to the same queue channel it came from. When the `queue` parameter is set to `null`, a message will be sent to the same channel it came from.

## How to create a custom Failure Middleware?

All you need is to implement the `MiddlewareFailureInterface` and add your implementation definition to the [configuration](#configuration).
This interface has the only method `handle` with these parameters:
- [`FailureHandlingRequest $request`](../../../src/Middleware/FailureHandling/FailureHandlingRequest.php) - a request for a message handling. It consists of
    - a [message](../../../src/Message/MessageInterface.php)
    - a `Throwable $exception` object thrown on the `request` handling
    - a queue the message came from
- `MessageFailureHandlerInterface $handler` - failure strategy pipeline continuation. Your Middleware should call `$pipeline->handle()` when it shouldn't interrupt failure pipeline execution.

> Note: your strategy have to check by its own if it should be applied. Look into [`SendAgainMiddleware::suites()`](../../../src/Middleware/FailureHandling/Implementation/SendAgainMiddleware.php#L54) for an example.
