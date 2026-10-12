# Envelope metadata and serialization

This document explains how envelope metadata is built and what survives serialization.

For basic envelope usage and concepts, see [Envelopes](envelopes.md).

## Metadata merging

Every envelope contributes its own metadata to the resulting metadata array. `Yiisoft\Queue\Message\Envelope` merges
the wrapped message metadata with the envelope's own metadata, the envelope's own values taking precedence:

```php
$message = new GenericMessage('my-type', null);
$envelope = new DelayEnvelope(new IdEnvelope($message, 7), 3.5);

$envelope->getMeta(); // ['yii-id' => 7, 'yii-delay' => 3.5]
```

`getType()` and `getPayload()` are delegated to the wrapped message, so an envelope is transparent for both.

## Envelopes are flattened, not stacked

`Envelope::__construct()` unwraps nested envelopes, so `getMessage()` always returns the innermost message:

```php
$envelope->getMessage(); // GenericMessage, not IdEnvelope
```

The envelope classes themselves are not recorded anywhere. What an envelope contributes is its metadata, and that is
all that is carried further.

## Serialization

`Yiisoft\Queue\Message\Serializer\MessageSerializer` writes three keys — `type`, `payload` and `meta` — and restores a
message by resolving the class from `type` and applying `withMeta()`. Envelope wrappers are therefore not rebuilt on
the way back; the values they carried are still in the metadata and can be read through the envelope that owns them:

```php
IdEnvelope::fromMessage($message)->getId();
DelayEnvelope::fromMessage($message)->getDelaySeconds();
```

> **Note:** do not register an `Envelope` subclass as the class for a message type in the class resolver. Unserializing
> such a type calls `Envelope::fromPayload()`, which always throws a `LogicException`. Register the wrapped message
> class instead.

## FailureEnvelope metadata merging

`Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope` stores failure-handling metadata under the
`FailureEnvelope::META_FAILURE` key (`"yii-failure"`).

Its constructor deep-merges the failure metadata already present on the message with the metadata being added, so
values accumulated by earlier failure-handling middlewares are preserved rather than overwritten.
