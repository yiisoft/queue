# Message handler

> If you are new to the concept of messages and handlers, read [Messages and handlers: concepts](messages-and-handlers.md) first.

The simplest setup requires no configuration at all: create a dedicated class implementing `Yiisoft\Queue\Message\MessageHandlerInterface` and use its FQCN as the handler name when pushing a message.

## HandlerInterface implementation (without name mapping)

If your handler implements `Yiisoft\Queue\Message\MessageHandlerInterface`, you can use the class FQCN as the message handler name. The DI container resolves the handler automatically.

> By default the [yiisoft/di](https://github.com/yiisoft/di) container resolves all FQCNs into corresponding class objects.

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

**Config**: Not needed.

**Pros**:

- Minimal configuration.
- Rename-safe within the same application (rename both the class and the message creation code together).
- Easy to unit-test the handler as a normal class.

**Cons**:

- Message names are PHP class names — works only when message creation and handler live in the same codebase.

**Use when**:

- Producer and consumer are the same application.
- You control message creation and can safely use FQCN as the handler name.

## When FQCN is not enough

When the producer is an external application or a different service, FQCN-based names create a hard dependency on PHP class names. In that case, use short stable handler names mapped to callables in config.

See [Message handler: named handlers and advanced formats](message-handler.md) for all supported definition formats, pitfalls, and recommendations.
