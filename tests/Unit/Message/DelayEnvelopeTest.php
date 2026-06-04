<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\DelayEnvelope;
use Yiisoft\Queue\Message\GenericMessage;

final class DelayEnvelopeTest extends TestCase
{
    public function testDelayEnvelope(): void
    {
        $message = new GenericMessage('test', ['data' => 'value']);
        $delayEnvelope = new DelayEnvelope($message, 300.5);

        self::assertSame($message, $delayEnvelope->getMessage());
        self::assertSame('test', $delayEnvelope->getType());
        self::assertSame(['data' => 'value'], $delayEnvelope->getData());
        self::assertSame(300.5, $delayEnvelope->getDelaySeconds());
        self::assertSame(
            [DelayEnvelope::META_DELAY_SECONDS => 300.5],
            $delayEnvelope->getMetadata(),
        );
    }

    public function testFromMessage(): void
    {
        $delayEnvelope = DelayEnvelope::fromMessage(
            (new GenericMessage('test', ['data' => 'value']))
                ->withMetadata([DelayEnvelope::META_DELAY_SECONDS => 150]),
        );

        self::assertSame(150.0, $delayEnvelope->getDelaySeconds());
        self::assertSame('test', $delayEnvelope->getType());
        self::assertSame(['data' => 'value'], $delayEnvelope->getData());
    }

    public function testFromMessageWithoutDelay(): void
    {
        $message = new GenericMessage('test', ['data' => 'value']);
        $delayEnvelope = DelayEnvelope::fromMessage($message);

        self::assertSame(0.0, $delayEnvelope->getDelaySeconds());
    }
}
