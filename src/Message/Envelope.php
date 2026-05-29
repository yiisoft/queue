<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

use LogicException;

abstract class Envelope implements MessageInterface
{
    /**
     * @psalm-var array<string, mixed>
     */
    protected readonly array $metadata;

    private readonly MessageInterface $message;

    public function __construct(MessageInterface $message, array $metadata)
    {
        $this->metadata = array_merge($message->getMetadata(), $metadata);

        while ($message instanceof self) {
            $message = $message->getMessage();
        }
        $this->message = $message;
    }

    final public static function fromData(string $type, mixed $data): static
    {
        throw new LogicException(
            'Envelopes cannot be created via "fromData()". Wrap an existing "MessageInterface" instance instead.',
        );
    }

    abstract public static function fromMessage(MessageInterface $message): static;

    final public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    final public function getType(): string
    {
        return $this->message->getType();
    }

    final public function getData(): mixed
    {
        return $this->message->getData();
    }

    final public function getMetadata(): array
    {
        return $this->metadata;
    }

    final public function withMetadata(array $metadata): static
    {
        return static::fromMessage($this->message->withMetadata($metadata));
    }
}
