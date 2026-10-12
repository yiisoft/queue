# Envelopes

An *envelope* is a message container that wraps another message and adds metadata.

An envelope extends the abstract `Yiisoft\Queue\Message\Envelope` class, which implements
`Yiisoft\Queue\Message\MessageInterface`.

## How an envelope behaves

An envelope is transparent to callers: it delegates the wrapped message type and data unchanged.

- `getType()` is delegated to the wrapped message.
- `getPayload()` is delegated to the wrapped message.

Envelopes modify the metadata returned by `getMeta()` and may provide convenience methods for accessing specific metadata entries (for example, `getId()` in an ID envelope).

## Creating envelopes

To wrap a message into an envelope, use either the envelope constructor or:

- `Envelope::fromMessage(MessageInterface $message): static`, which restores the envelope's own parameters from the
  message metadata.

`Envelope::fromPayload()` is inherited from `MessageInterface`, but it always throws a `LogicException`: an envelope
cannot be created from a raw payload, it can only wrap an existing message.

## Built-in envelopes

### IdEnvelope

`Yiisoft\Queue\Message\IdEnvelope` adds a message identifier into metadata under the `IdEnvelope::META_ID` key (`"yii-id"`).

This envelope is used to carry the adapter-provided message ID through the message lifecycle.

### FailureEnvelope

`Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope` stores failure-handling metadata.

See [Errors and retryable messages](error-handling.md) for error handling concepts and [Envelope metadata and serialization](envelopes-metadata-internals.md) for details on how failure metadata is merged.
