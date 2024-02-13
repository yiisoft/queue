<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * ID envelope allows to identify a message.
 */
final class IdEnvelope implements EnvelopeInterface
{
    use EnvelopeTrait;

    public const MESSAGE_ID_KEY = 'yii-message-id';

    public function __construct(
        private MessageInterface $message,
        private string|int|null $id = null,
    ) {
    }

    public function setId(string|int|null $id): void
    {
        $this->id = $id;
    }

    public function getId(): string|int|null
    {
        return $this->id ?? $this->message->getMetadata()[self::MESSAGE_ID_KEY] ?? null;
    }

    public function getEnvelopeMetadata(): array
    {
        return [self::MESSAGE_ID_KEY => $this->getId()];
    }
}
