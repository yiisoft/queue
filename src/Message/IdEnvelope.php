<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

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

    public function getId(): string|int|null
    {
        return $this->metadata[self::MESSAGE_ID_KEY];
    }
}
