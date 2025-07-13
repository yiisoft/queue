<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * ID envelope allows to identify a message.
 */
final class IdEnvelope extends Envelope
{
    public const MESSAGE_ID_KEY = 'yii-message-id';

    public function __construct(
        protected MessageInterface $message,
        private readonly string|int|null $id,
    ) {
    }

    public static function fromMessage(MessageInterface $message): static
    {
        /** @var mixed $rawId */
        $rawId = $message->getMetadata()[self::MESSAGE_ID_KEY] ?? null;

        /** @var int|string|null $id */
        $id = match (true) {
            $rawId === null => null,
            is_string($rawId) => $rawId,
            is_int($rawId) => $rawId,
            is_object($rawId) && method_exists($rawId, '__toString') => (string)$rawId,
            default => throw new \InvalidArgumentException(sprintf('Message ID must be string|int|null, %s given.', get_debug_type($rawId))),
        };

        return new self($message, $id);
    }

    public function getId(): string|int|null
    {
        return $this->id;
    }

    protected function getEnvelopeMetadata(): array
    {
        return [self::MESSAGE_ID_KEY => $this->getId()];
    }
}
