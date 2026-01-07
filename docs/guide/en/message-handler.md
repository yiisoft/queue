# Message handler

A *message handler* is what processes a queue message. Internally, `Yiisoft\Queue\Worker\Worker` resolves a handler by the message handler name (`MessageInterface::getHandlerName()`) and then executes it through `Yiisoft\Injector\Injector`.

Handler definitions are configured in:

- `$params['yiisoft/queue']['handlers']` when using [yiisoft/config](https://github.com/yiisoft/config), or
- the `$handlers` argument of `Yiisoft\Queue\Worker\Worker` when creating it manually.

## Supported handler definition formats

### 1. HandlerInterface implementation (without mapping)

If your handler is a dedicated class implementing `Yiisoft\Queue\Message\MessageHandlerInterface`, you can use the class name itself as the message handler name (FQCN) if your DI container can resolve the handler class.

> By default the [yiisoft/di](https://github.com/yiisoft/di) container resolves all FQCNs into corresponding class objects.

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

### 2. Named handlers

In this case you should use a proper handler name when pushing a `Message` instead of a handler class name as in the example above:

```php
new \Yiisoft\Queue\Message\Message('send-email', ['data' => '...']);
```

**Config**:

Map handler name to a closure in `$params`:

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'send-email' => /** handler definition */,
        ],
    ],
];
```

Handler definition should be either an [extended callable definition](./callable-definitions-extended.md) or a string for your DI container to resolve a `MessageHandlerInterface` instance.

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

- A string definition is treated as a DI container ID first. If the container doesn't have such entry, it is resolved as a callable only when it is a valid PHP callable.
- A class-string that is not resolvable via `$container->has()` will not be auto-instantiated.
- [yiisoft/definitions](https://github.com/yiisoft/definitions) array format (like `['class' => ..., '__construct()' => ...]`) is **not** supported for handlers.

## Recommended handler implementation styles

- Prefer a dedicated handler class registered in DI.
- For maximal compatibility with the worker resolution rules either:
  - Implement `MessageHandlerInterface`
  - Make the handler invokable (`__invoke(MessageInterface $message): void`)
  - Provide `[HandlerClass::class, 'handle']` and keep `handle(MessageInterface $message): void` as the entry point

## Config location ([yiisoft/config](https://github.com/yiisoft/config))

When using [yiisoft/config](https://github.com/yiisoft/config), configure handlers under the [`yiisoft/queue`](https://github.com/yiisoft/queue) params key:

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
