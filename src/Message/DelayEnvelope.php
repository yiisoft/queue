<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

final class DelayEnvelope extends Envelope
{
    public const META_DELAY_SECONDS = 'yii-delay';

    public function __construct(MessageInterface $message, private readonly int $delaySeconds)
    {
        parent::__construct($message);
    }

    public static function fromMessage(MessageInterface $message): static
    {
        /** @var int|string $delaySeconds */
        $delaySeconds = $message->getMetadata()[self::META_DELAY_SECONDS] ?? 0;

        return new self($message, (int) $delaySeconds);
    }

    public function getDelaySeconds(): int
    {
        return $this->delaySeconds;
    }

    protected function getEnvelopeMetadata(): array
    {
        return [self::META_DELAY_SECONDS => $this->delaySeconds];
    }
}
