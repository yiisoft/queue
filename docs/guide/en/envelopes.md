# Envelopes

An *envelope* is a message container that wraps another message and adds metadata.

An envelope implements `Yiisoft\Queue\Message\EnvelopeInterface`, which itself extends `Yiisoft\Queue\Message\MessageInterface`.

## How an envelope behaves

An envelope acts like the wrapped message:

- `getHandlerName()` is delegated to the wrapped message.
- `getData()` is delegated to the wrapped message.

What an envelope adds is `getMetadata()`.

## Metadata and envelope stacking

Every envelope contributes its own metadata to the resulting metadata array.

`Yiisoft\Queue\Message\Envelope` (base class) also maintains an envelope stack in message metadata under `EnvelopeInterface::ENVELOPE_STACK_KEY` (`"envelopes"`).

When `getMetadata()` is called on an envelope, it returns:

- the wrapped message metadata,
- plus an updated `"envelopes"` stack (previous stack + current envelope class),
- plus envelope-specific metadata.

Because envelopes wrap other messages, multiple envelopes form a stack.

## Creating envelopes

To wrap a message into an envelope, envelope classes provide:

- `EnvelopeInterface::fromMessage(MessageInterface $message): static`

and, via `MessageInterface` inheritance, also support:

- `Envelope::fromData(string $handlerName, mixed $data, array $metadata = []): static`

## Restoring envelopes from metadata

If metadata contains the `"envelopes"` key with an array of envelope class names, the serializer will try to rebuild the stack by wrapping the message with each envelope class in the given order.

During this process:

- The `"envelopes"` key is removed from the base message metadata (it is set to an empty array before creating the base message).
- Each envelope class from the list is applied to the message using `EnvelopeInterface::fromMessage(...)`.
- A value is applied only if it is a string, the class exists, and it implements `EnvelopeInterface`. Otherwise it is ignored.

## Built-in envelopes

### IdEnvelope

`Yiisoft\Queue\Message\IdEnvelope` adds a message identifier into metadata under the `IdEnvelope::MESSAGE_ID_KEY` key (`"yii-message-id"`).

This envelope is used to carry the adapter-provided message ID through the message lifecycle.

### FailureEnvelope

`Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope` stores failure-handling metadata under the `FailureEnvelope::FAILURE_META_KEY` key (`"failure-meta"`).

The envelope merges failure metadata when building `getMetadata()`.
