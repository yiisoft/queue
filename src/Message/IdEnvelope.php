<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * ID envelope allows to identify a message.
 */
final class IdEnvelope extends AbstractEnvelope
{
    public const MESSAGE_ID_KEY = 'yii-message-id';

    public function __construct(
        MessageInterface $message,
        private readonly string|int|null $id = null,
    ) {
        parent::__construct($message);
    }

    public static function fromMessage(MessageInterface $message): self
    {
        return new self($message, $message->getMetadata()[self::MESSAGE_ID_KEY] ?? null);
    }

    public function getId(): string|int|null
    {
        return $this->id ?? $this->metadata[self::MESSAGE_ID_KEY] ?? null;
    }

    protected function getEnvelopeMetadata(): array
    {
        return [self::MESSAGE_ID_KEY => $this->getId()];
    }
}
