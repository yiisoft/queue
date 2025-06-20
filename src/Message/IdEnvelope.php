<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * ID envelope allows identifying a message.
 */
final class IdEnvelope implements EnvelopeInterface
{
    use EnvelopeTrait;

    public const MESSAGE_ID_KEY = 'yii-message-id';

    public function __construct(
        MessageInterface $message,
        private readonly string|int|null $id = null,
    ) {
        $this->message = $message;
    }

    public static function fromMessage(MessageInterface $message): self
    {
        return new self($message, $message->getMetadata()[self::MESSAGE_ID_KEY] ?? null);
    }

    public function setId(string|int|null $id): void
    {
        $this->id = $id;
    }

    public function getId(): string|int|null
    {
        return $this->id ?? $this->message->getMetadata()[self::MESSAGE_ID_KEY] ?? null;
    }

    private function getEnvelopeMetadata(): array
    {
        return [self::MESSAGE_ID_KEY => $this->getId()];
    }
}
