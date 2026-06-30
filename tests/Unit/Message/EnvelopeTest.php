<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use LogicException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Stubs\DummyEnvelope;

final class EnvelopeTest extends TestCase
{
    public function testFromData(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Envelopes cannot be created via "fromPayload()". Wrap an existing "MessageInterface" instance instead.',
        );
        DummyEnvelope::fromPayload('test', []);
    }

    public function testWithMetadata(): void
    {
        $envelope = DummyEnvelope::fromMessage(new GenericMessage('test', 'data'));

        $result = $envelope->withMetadata(['key' => 'value']);

        $this->assertInstanceOf(DummyEnvelope::class, $result);
        $this->assertSame(['key' => 'value'], $result->getMetadata());
    }
}
