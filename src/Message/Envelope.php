<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

use LogicException;

/**
 * @template TMetadata of MessageMetadata
 *
 * @psalm-import-type MessageMetadata from MessageInterface
 */
abstract class Envelope implements MessageInterface
{
    /**
     * @psalm-var TMetadata
     */
    protected readonly array $metadata;

    private readonly MessageInterface $message;

    /**
     * @psalm-param TMetadata $metadata
     */
    public function __construct(MessageInterface $message, array $metadata)
    {
        /** @var TMetadata */
        $this->metadata = array_merge($message->getMetadata(), $metadata);

        while ($message instanceof self) {
            $message = $message->getMessage();
        }
        $this->message = $message;
    }

    /**
     * Envelopes cannot be created from a raw payload. Use {@see fromMessage()} to wrap an existing message instead.
     *
     * @throws LogicException Always, since this method is not supported for envelopes.
     */
    final public static function fromPayload(string $type, mixed $payload): static
    {
        throw new LogicException(
            'Envelopes cannot be created via "fromPayload()". Wrap an existing "MessageInterface" instance instead.',
        );
    }

    /**
     * Creates an envelope for the given message, restoring the envelope's own parameters from the message metadata.
     *
     * @param MessageInterface $message The message to create an envelope for.
     */
    abstract public static function fromMessage(MessageInterface $message): static;

    final public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    final public function getType(): string
    {
        return $this->message->getType();
    }

    final public function getPayload(): bool|int|float|string|array|null
    {
        return $this->message->getPayload();
    }

    /**
     * @psalm-return TMetadata
     */
    final public function getMetadata(): array
    {
        return $this->metadata;
    }

    final public function withMetadata(array $metadata): static
    {
        return static::fromMessage($this->message->withMetadata($metadata));
    }
}
