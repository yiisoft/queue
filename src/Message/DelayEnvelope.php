<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

use function is_array;

/**
 * @extends Envelope<array{
 *      yii-delay: float,
 *      ...<string, scalar|null|array<scalar|null|array>>
 *  }>
 */
final class DelayEnvelope extends Envelope
{
    public const META_DELAY_SECONDS = 'yii-delay';

    public function __construct(MessageInterface $message, float $delaySeconds)
    {
        parent::__construct($message, [self::META_DELAY_SECONDS => $delaySeconds]);
    }

    public static function fromMessage(MessageInterface $message): static
    {
        $raw = $message->getMeta()[self::META_DELAY_SECONDS] ?? null;
        return new self($message, is_array($raw) ? 0.0 : (float) $raw);
    }

    public function getDelaySeconds(): float
    {
        return $this->meta[self::META_DELAY_SECONDS];
    }
}
