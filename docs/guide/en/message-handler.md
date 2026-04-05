# Message handler advanced

This page covers named handler definitions, callable formats, pitfalls, and recommended implementation styles.

For the zero-config FQCN approach (same-app producer and consumer), see [Message handler: simple setup](message-handler-simple.md).
For a conceptual overview of what messages and handlers are, see [Messages and handlers: concepts](messages-and-handlers.md).

Handler definitions are configured in:

- `$params['yiisoft/queue']['handlers']` when using [yiisoft/config](https://github.com/yiisoft/config), or
- the `$handlers` argument of `Yiisoft\Queue\Worker\Worker` when creating it manually.

## Supported handler definition formats

### Named handlers

Use a short stable handler name when pushing a `Message` instead of a PHP class name:

```php
use Yiisoft\Queue\Message\Message;

new Message('send-email', ['data' => '...']); // "send-email" is the handler name here
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

For the simpler FQCN-based approach that requires no mapping, see [Message handler: simple setup](message-handler-simple.md).

## When mapping by short names is a better idea

While FQCN-as-name is convenient inside a single application, mapping by a short name is often a better contract. That is true when messages are produced outside the current codebase, or when you want to create a stable public API for inter-service communication.

**Typical cases**:

- Another application pushes messages to the same broker.
- A different language/runtime produces messages.

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
