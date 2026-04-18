# Message handler: advanced setup

This page covers handler definitions by message type, callable formats, pitfalls, and valid handler signatures.

If you haven't read [Message handler: simple setup](message-handler.md) yet, start there — it introduces handler classes and the zero-config FQCN approach.
For a conceptual overview of what messages and handlers are, see [Messages and handlers: concepts](messages-and-handlers.md).

Handler definitions are configured in:

- `$params['yiisoft/queue']['handlers']` when using [yiisoft/config](https://github.com/yiisoft/config), or
- the `$handlers` argument of `Yiisoft\Queue\Worker\Worker` when creating it manually.

## Supported handler definition formats

### Handlers mapped by short message type

Use a short stable message type when pushing a `Message` instead of a PHP class name:

```php
use Yiisoft\Queue\Message\Message;

new Message('send-email', ['data' => '...']); // "send-email" is the message type here
```

**Config**:

Map message type to a handler in `$params`:

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'send-email' => /** handler definition */,
        ],
    ],
];
```

Handler definition should be either an [extended callable definition](./callable-definitions-extended.md) or a container identifier that resolves to a `MessageHandlerInterface` instance.


## When mapping by short names is a better idea

While FQCN-as-name is convenient inside a single application, mapping by a short name is often a better contract. That is true when messages are produced outside the current codebase, or when you want to create a stable public API for inter-service communication.

**Typical cases**:

- Another application pushes messages to the same broker.
- A different language/runtime produces messages.

In these cases you typically keep message types small and stable, and map them in config:

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

- A PHP class name that is not registered in the DI container will not be auto-instantiated.
- [yiisoft/definitions](https://github.com/yiisoft/definitions) array format (like `['class' => ..., '__construct()' => ...]`) is **not** supported for handlers.

## Valid handler signatures

The worker recognises three callable signatures:

- `MessageHandlerInterface` — implement the interface; the worker calls `handle(MessageInterface $message): void` directly (covered in [Message handler](message-handler.md)).
- Invokable class — add `__invoke(MessageInterface $message): void`.
- Explicit method — reference as `[HandlerClass::class, 'handle']` with `handle(MessageInterface $message): void` as the entry point.

## Config location (yiisoft/config)

When using [yiisoft/config](https://github.com/yiisoft/config), configure handlers under the [`yiisoft/queue`](https://github.com/yiisoft/queue) params key:

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'message-type' => [FooHandler::class, 'handle'],
        ],
    ],
];
```

This config is consumed by the DI definitions from [`config/di.php`](../../../config/di.php) where the `Worker` is constructed with `$params['yiisoft/queue']['handlers']`.
