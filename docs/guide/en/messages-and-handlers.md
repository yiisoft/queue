# Messages and handlers

Every unit of work in a queue is called a *job*. A job has two independent parts:

- **Message** — a lightweight, serializable payload that describes *what* needs to be done and carries the data needed to do it. The message knows nothing about how it will be processed.
- **Handler** — a piece of code that receives the message and performs the actual work.

This separation is intentional and important. Understanding it will save you from confusion when configuring producers, consumers, and cross-application queues.

## Producer and consumer roles

A *producer* creates messages and pushes them onto the queue. A *consumer* (worker) pulls messages from the queue and executes the matching handler.

```
Producer side                      Consumer side
─────────────────────────────      ──────────────────────────────────
new Message('send-email', …)  →→→  Worker resolves handler → executes
         (payload only)                     (logic only)
```

The producer only needs to know the message name and its data. It does not need to know anything about how the message will be processed, or even in which application.

The consumer only needs to know how to handle a message by name. It does not need to know where the message came from.

This means the producer and consumer can be:

- The same class in the same application (most common case).
- Different classes in the same application.
- **Completely different applications**, possibly written in different languages.

## Message: payload only

A message carries just enough data to perform the job:

```php
new \Yiisoft\Queue\Message\Message('send-email', [
    'to'      => 'user@example.com',
    'subject' => 'Welcome',
]);
```

The message has:

- A **handler name** — a string used by the worker to look up the correct handler.
- A **data payload** — arbitrary data the handler needs. Must be serializable.

The message has no methods, no business logic, no dependencies. It is a value object — a data envelope.

## Handler: logic only

The handler receives the message and acts on it:

```php
final class SendEmailHandler implements \Yiisoft\Queue\Message\MessageHandlerInterface
{
    public function __construct(private Mailer $mailer) {}

    public function handle(\Yiisoft\Queue\Message\MessageInterface $message): void
    {
        $data = $message->getData();
        $this->mailer->send($data['to'], $data['subject']);
    }
}
```

The handler can have any dependencies injected through the DI container. The message payload remains dumb data.

## Why the separation matters

### Versioning

When payload and logic are one object, renaming a class or changing its constructor breaks all messages that are already sitting in the queue. With separated payload you can evolve the handler independently: rename it, replace it, or run multiple handler versions side by side, as long as the message name and data contract stay compatible.

### Cross-application queues

When the producer and consumer live in different applications (or even different repos), the producer cannot import the consumer's handler classes. With the separated model the producer only sends a name + data; the consumer maps that name to a local handler class. No shared class dependencies are needed.

### Cross-language interoperability

Because the payload is just data, any language can produce or consume it. A Python service or a Node.js microservice can push a `{"name":"send-email","data":{…}}` JSON object and `yiisoft/queue` will process it correctly. No PHP class names appear in the wire format.

## Why JSON is the default serialization

By default, `yiisoft/queue` serializes message payloads as JSON (`JsonMessageSerializer`). JSON was chosen intentionally:

- **Human-readable** — you can inspect a message in a broker dashboard without any tools.
- **Language-agnostic** — every language and runtime can produce and parse JSON.
- **Fast and lightweight** — no class metadata, no object graphs, no PHP-specific format.
- **Forces payload discipline** — if your data cannot be expressed as a JSON-encodable value (strings, numbers, booleans, null, arrays, and objects), it is a sign the payload carries too much. Keep payloads simple: IDs, strings, primitive values.

You can replace `JsonMessageSerializer` with your own implementation by rebinding `MessageSerializerInterface` in DI, but the default works for the vast majority of use cases.

## Migration note: Yii2 queue

In `yii2-queue`, a job was a single PHP object that contained both the payload *and* the execution logic in one class (via the `JobInterface::execute()` method):

```php
// Yii2 style — payload and logic in one class
class SendEmailJob implements JobInterface
{
    public string $to;
    public string $subject;

    public function execute($queue): void
    {
        Yii::$app->mailer->send($this->to, $this->subject);
    }
}
```

This looked convenient at first but created real problems:

- **The handler class had to be available on both the producer and the consumer.** In a microservice setup this forced sharing a PHP class (and its dependency tree) across applications.
- **PHP class names were baked into the serialized payload.** Renaming a class without a migration was risky. Versioning required workarounds.
- **The job carried behavior**, making it impossible to produce or consume messages from non-PHP services without custom serializers.
- **Testing was harder** — you had to mock application services inside the job class.

`yiisoft/queue` solves all of these by keeping the message as pure data and the handler as pure logic. The two evolve independently and can live in separate codebases.

## Next steps

- [Message handler: simple setup](message-handler-simple.md) — zero-config FQCN handlers for single-application use.
- [Message handler: named handlers and advanced formats](message-handler.md) — named handlers, callable definitions, and cross-application mapping.
- [Consuming messages from external systems](consuming-messages-from-external-systems.md) — producing valid JSON payloads from non-PHP services.
