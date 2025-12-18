# Message handler

A *message handler* is what processes a queue message. Internally, `Yiisoft\Queue\Worker\Worker` resolves a handler by the message handler name (`MessageInterface::getHandlerName()`) and then executes it through `Yiisoft\Injector\Injector`.

Handler definitions are configured in:

- `$params['yiisoft/queue']['handlers']` when using `yiisoft/config`, or
- the `$handlers` argument of `Yiisoft\Queue\Worker\Worker` when creating it manually.

## Supported handler definition formats

`Worker` supports a limited set of formats. Below are the exact formats that are converted to a callable.

### 1. HandlerInterface implementation (without mapping)

If your handler is a dedicated class implementing `Yiisoft\Queue\Message\MessageHandlerInterface`, you can use the class name itself as the message handler name.

This is the default and most convenient option when the producer and the consumer are the same application.

In this setup, you usually don't need to configure handler mapping at all as long as your DI container can resolve the handler class.

**Message**:

```php
new \Yiisoft\Queue\Message\Message(\App\Queue\RemoteFileHandler::class, ['url' => '...']);
```

**Handler**:

```php
final class RemoteFileHandler implements \Yiisoft\Queue\Message\MessageHandlerInterface
{
    public function handle(\Yiisoft\Queue\Message\MessageInterface $message): void
    {
        // Handle the message
    }
}
```

**Config**:

Not needed

**Pros**:

- Minimal configuration.
- Stable refactoring inside the same application (rename-safe if you rename the class and update the producer code).
- Easy to unit-test the handler as a normal class.

**Cons**:

- Couples produced messages to PHP class names.
- Requires producer and consumer to share the same naming contract (usually “same app”).

**Use when**:

- Producer and consumer are the same application.
- You control message creation code and can safely use FQCN as the handler name.

### 2. Closure

In this and all the cases below, you should use a proper handler name when pushing a `Message` instead of a handler class name in the example above:

```php
new \Yiisoft\Queue\Message\Message('send-email', ['data' => '...']);
```

**Config**:

Map handler name to a closure in `$params`:

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'send-email' => static fn (\Yiisoft\Queue\Message\MessageInterface $message, \App\Foo $foo) => $foo->bar($message->getData()),
        ],
    ],
];
```

**How it works**:

- A `Closure` is accepted as-is.
- The worker executes it using `Injector`, so you may type-hint extra dependencies in the closure parameters.

**Pros**:

- Very simple for small tasks and quick prototypes.
- Easy to inject extra services via `Injector`.

**Cons**:

- Less reusable and harder to unit-test than a dedicated class.
- Easy to accidentally put non-trivial business logic into config.
- Harder to maintain and refactor as the logic grows.

**Use when**:

- You're prototyping async workflows and going to refactor it later into a proper handler class.
- You want a quick "glue" handler that delegates to services.

### 3. Container ID string

**Config**:

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'file-download' => FileDownloader::class,
        ],
    ],
];
```

**How it works**:

The handler object is retrieved from the DI container. In this case the handler class should either

- have the `__invoke()` method, which receives a message parameter,
- implement `Yiisoft\Queue\Message\MessageHandlerInterface` (then the `$handler->handle(...)` method is called).

If the resolved service is neither callable nor a `MessageHandlerInterface`, the handler is treated as invalid.

**Pros**:

- Short and clean configuration.
- Supports invokable handlers and `MessageHandlerInterface` handlers.

**Cons**:

&mdash;

**Use when**:

- You already register handlers in DI (recommended for production).
- You prefer invokable handlers (`__invoke`) or `MessageHandlerInterface`.

### 4. Two-element array of strings: `[classOrServiceId, method]`

**Config**:

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'file-download' => [FileDownloader::class, 'handle'],
            'file-download2' => [$handler, 'handle'],
        ],
    ],
];
```

**How it works**:

- If the class exists:
  - If the method is static, it is called statically: `[$className, $methodName]`. Dependencies may be passed *to the provided method* in case they are resolvable from the DI container.
  - If the first element is an object instance, it is called as `$firstElement->$methodName(...)` with dependency injection applied *to the $methodName*.
  - If the method is not static, the class must be resolvable from the DI container, and the worker calls `$container->get($className)->$methodName(...)`. DI container will also resolve dependencies declared in the *class constructor*.

**Pros**:

- Explicit method name, good for “classic” `handle()` methods.
- Supports static methods for pure, dependency-free handlers.

**Cons**:

- Harder to maintain and refactor than regular class definitions with either `__invoke` method or `MessageHandlerInterface` implementation.

**Use when**:

- You want to use static handlers (rare, but can be useful for pure transforms).
- You want to group different handlers in a single class for organizational purposes.

## When mapping by short names is a better idea

While FQCN-as-name is convenient inside a single application, mapping by a short name is often a better contract. That is true when messages are produced outside the current codebase, or when you want to create a stable public API for inter-service communication.

**Typical cases**:

- Another application pushes messages to the same broker.
- A different language/runtime produces messages.
- You want a stable public contract that is independent of your PHP namespaces and refactorings.

In these cases you typically keep message handler names small and stable, and map them in config:

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'file-download' => \App\Queue\RemoteFileHandler::class,
        ],
    ],
];
```

This way external producers never need to know your internal PHP class names.

## Common pitfalls and unsupported formats

- A string definition is **not** treated as a function name. It is treated only as a DI container ID.
- A class-string that is not resolvable via `$container->has()` will not be auto-instantiated.
- `yiisoft/definitions` array format (like `['class' => ..., '__construct()' => ...]`) is **not** supported for handlers.

## Recommended handler implementation styles

- Prefer a dedicated handler class registered in DI.
- For maximal compatibility with the worker resolution rules either:
  - Implement `MessageHandlerInterface`
  - Make the handler invokable (`__invoke(MessageInterface $message): void`)
  - Provide `[HandlerClass::class, 'handle']` and keep `handle(MessageInterface $message): void` as the entry point

## Config location (yiisoft/config)

When using `yiisoft/config`, configure handlers under the `yiisoft/queue` params key:

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'handler-name' => [FooHandler::class, 'handle'],
        ],
    ],
];
```

This config is consumed by the DI definitions from `config/di.php` where the `Worker` is constructed with `$params['yiisoft/queue']['handlers']`.
