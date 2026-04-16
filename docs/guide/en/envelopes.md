# Envelopes

An *envelope* is a message container that wraps another message and adds metadata.

An envelope implements `Yiisoft\Queue\Message\EnvelopeInterface`, which itself extends `Yiisoft\Queue\Message\MessageInterface`.

## How an envelope behaves

An envelope is transparent to callers: it delegates the wrapped message's handler name and data unchanged.

- `getHandlerName()` is delegated to the wrapped message.
- `getData()` is delegated to the wrapped message.

Envelopes modify the metadata returned by `getMetadata()` and may provide convenience methods for accessing specific metadata entries (for example, `getId()` in an ID envelope).

## Creating envelopes

To wrap a message into an envelope, envelope classes provide:

- `EnvelopeInterface::fromMessage(MessageInterface $message): static`

and, via `MessageInterface` inheritance, also support:

- `Envelope::fromData(string $handlerName, mixed $data, array $metadata = []): static`

## Built-in envelopes

### IdEnvelope

`Yiisoft\Queue\Message\IdEnvelope` adds a message identifier into metadata under the `IdEnvelope::MESSAGE_ID_KEY` key (`"yii-message-id"`).

This envelope is used to carry the adapter-provided message ID through the message lifecycle.

### FailureEnvelope

`Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope` stores failure-handling metadata.

See [Errors and retryable messages](error-handling.md) for error handling concepts and [Envelope metadata and stack reconstruction](envelopes-metadata-internals.md) for details on how failure metadata is merged.
