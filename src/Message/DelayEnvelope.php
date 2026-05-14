<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

final class DelayEnvelope extends Envelope
{
    public const META_DELAY_SECONDS = 'yii-delay';

    public function __construct(MessageInterface $message, float $delaySeconds)
    {
        parent::__construct($message, [self::META_DELAY_SECONDS => $delaySeconds]);
    }

    public static function fromMessage(MessageInterface $message): static
    {
        /** @var float|int|string $delaySeconds */
        $delaySeconds = $message->getMetadata()[self::META_DELAY_SECONDS] ?? 0.0;
        return new self($message, (float) $delaySeconds);
    }

    public function getDelaySeconds(): float
    {
        return $this->metadata[self::META_DELAY_SECONDS] ?? 0.0;
    }
}
