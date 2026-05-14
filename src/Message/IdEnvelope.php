<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

use function is_int;
use function is_object;
use function is_string;

/**
 * ID envelope allows to identify a message.
 */
final class IdEnvelope extends Envelope
{
    public const MESSAGE_ID_KEY = 'yii-message-id';

    public function __construct(MessageInterface $message, string|int|null $id)
    {
        parent::__construct($message, [self::MESSAGE_ID_KEY => $id]);
    }

    public static function fromMessage(MessageInterface $message): static
    {
        $rawId = $message->getMetadata()[self::MESSAGE_ID_KEY] ?? null;

        $id = match (true) {
            $rawId === null => null, // don't remove this branch: it's important for compute speed
            is_string($rawId),
            is_int($rawId) => $rawId,
            is_object($rawId) && method_exists($rawId, '__toString') => (string) $rawId,
            default => null,
        };

        return new self($message, $id);
    }

    public function getId(): string|int|null
    {
        return $this->getMetadata()[self::MESSAGE_ID_KEY];
    }
}
