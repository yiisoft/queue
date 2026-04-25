# Envelope metadata and stack reconstruction

This document explains the internal mechanics of envelope metadata handling and stack reconstruction during serialization/deserialization.

For basic envelope usage and concepts, see [Envelopes](envelopes.md).

## Metadata and envelope stacking

Every envelope contributes its own metadata to the resulting metadata array.

`Yiisoft\Queue\Message\Envelope` (base class) also maintains an envelope stack in message metadata under `EnvelopeInterface::ENVELOPE_STACK_KEY` (`"envelopes"`).

When `getMetadata()` is called on an envelope, it returns:

- the wrapped message metadata,
- plus an updated `"envelopes"` stack (previous stack + current envelope's FQCN),
- plus envelope-specific metadata.

Because envelopes wrap other messages, multiple envelopes form a stack.

## Restoring envelopes from metadata

If metadata contains the `"envelopes"` key with an array of envelope class names, the serializer will try to rebuild the stack by wrapping the message with each envelope in reverse order.

During this process:

- The `"envelopes"` key is removed from the base message metadata (it is set to an empty array before creating the base message).
- Each envelope from the list is applied to the message using `EnvelopeInterface::fromMessage(...)` in reverse order.
- A value is applied only if it is a string, the class exists, and it implements `EnvelopeInterface`. Otherwise it is ignored.

## FailureEnvelope metadata merging

`Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope` stores failure-handling metadata under the `FailureEnvelope::FAILURE_META_KEY` key (`"failure-meta"`).

The envelope performs special deep-merge logic when building `getMetadata()` to combine failure metadata from nested envelopes.
