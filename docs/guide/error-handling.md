# Error handling on message processing

Often when some message handling is failing, we want to retry its execution a couple more times or redirect it to another queue channel. This can be done in `yiisoft/yii-queue` with _Failure Strategies_. They are triggered each time message processing is interrupted with any `Throwable`. 

## Configuration

Here will be described configuration via `yiisoft/config`. If you don't use it - you should add `FailureStrategyMiddleware` to the queue consuming pipeline by your own and configure the `\Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\DispatcherFactory` the way it is described below.  

Configuration should be passed to the `yiisoft/yii-queue.fail-strategy-pipelines` key of the `params` config. You can also define different failure handling pipelines for each queue channel. Let's see and describe an example:

```php
'yiisoft/yii-queue' => [
    'fail-strategy-pipelines' => [
        DispatcherFactory::DEFAULT_PIPELINE => [
            [
                'class' => SendAgainStrategy::class,
                '__construct()' => ['id' => 'default-first-resend', 'queue' => null], 
            ],
            static fn (QueueFactoryInterface $factory) => new SendAgainStrategy(
                id: 'default-second-resend', 
                queue: $factory->get('failed-messages'),
            ),
        ],
        
        'failed-messages' => [
            [
                'class' => ExponentialDelayStrategy::class,
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

Keys here except `DispatcherFactory::DEFAULT_PIPELINE` are queue channel names, and values are lists of `FailureStrategyInterface` definitions. `DispatcherFactory::DEFAULT_PIPELINE` defines a default pipeline to apply to channels without explicitly defined failure strategy pipeline. Each strategy definition must be one of:
- A ready-to-use `FailureStrategyInterface` object like `new FooStrategy()`.
- A valid definition for the [yiisoft/definitions](https://github.com/yiisoft/definitions). It must describe an object, implementing the `FailureStrategyInterface`.
- A callable: `fn() => // do stuff`, `$object->foo(...)`, etc. It will be executed through the `yiisoft/injector`, so all the dependencies of your callable will be resolved. You can also define a "callable-looking" array, where object will be instantiated with a DI container: `[FooStrategy::class, 'handle']`.
- A string for your DI container to resolve the middleware, e.g. `FooStrategy::class`.

In the example above failures will be handled this way:

1. For the first time message will be resent to the same queue channel immediately.
2. If it fails again, it will be resent to the queue channel named `failed-messages`.
3. From now on it will be resent to the same queue channel (`failed-messages`) up to 30 times with a delay from 5 to 60 seconds, increased 1.5 times each time the message fails again.
4. If the message handler throw an exception one more (33rd) time, the exception will not be caught.

Failures of messages, which are initially sent to the `failed-messages` channel, will only be handled by the 3rd and the 4th points of this list.

## Default failure handling strategies

Let's see the built-in defaults.

### SendAgainStrategy

This strategy simply resends the given message to a queue. Let's see the constructor parameters through which it's configured:

- `id` - A unique string. Allows to use this strategy more than once for the same message, just like in example above.
- `maxAttempts` - Maximum attempts count for this strategy with the given $id before it will give up.
- `queue` - The strategy will send the message to the given queue when it's not `null`. That means you can use this strategy to push a message not to the same queue channel it came from. When the `queue` parameter is set to `null`, a message will be sent to the same channel it came from.

### ExponentialDelayStrategy

This strategy does the same thing as the `SendAgainStrategy` with a single difference: it resends a message with an exponentially increasing delay. The delay **must** be implemented by the used `AdapterInterface` implementation.

It's configured via constructor parameters, too. Here they are:

- `id` - A unique string allows to use this strategy more than once for the same message, just like in example above.
- `maxAttempts` - Maximum attempts count for this strategy with the given $id before it will give up.
- `delayInitial` - The initial delay that will be applied to a message for the first time. It must be a positive float. 
- `delayMaximum` - The maximum delay which can be applied to a single message. Must be above the `delayInitial`.
- `exponent` - Message handling delay will be increased by this multiplication each time it fails.
- `queue` - The strategy will send the message to the given queue when it's not `null`. That means you can use this strategy to push a message not to the same queue channel it came from. When the `queue` parameter is set to `null`, a message will be sent to the same channel it came from.

## How to create a custom Failure Strategy?

All you need is to implement the `FailureStrategyInterface` and add your implementation definition to the [configuration](#configuration).
This interface has the only method `handle`. And the method has these parameters:
- `ConsumeRequest $request` - a request for a message handling. It consists of a message and a queue the message came from.
- `Throwable $exception` - an exception thrown on the `request` handling
- `PipelineInterface $pipeline` - failure strategy pipeline continuation. Your Strategy should call `$pipeline->handle()` when it doesn't interrupt failure pipeline execution.

> Note: your strategy have to check by its own if it should be applied. Look into [`SendAgainStrategy::suites()`](../../src/Middleware/Implementation/FailureStrategy/Strategy/SendAgainStrategy.php#L52) for an example.
