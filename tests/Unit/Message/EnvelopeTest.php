<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Tests\App\DummyEnvelope;
use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\Message;

final class EnvelopeTest extends TestCase
{
    public function testFromData(): void
    {
        $handlerName = 'test-handler';
        $data = ['key' => 'value'];
        $metadata = ['meta' => 'data'];

        $envelope = DummyEnvelope::fromData($handlerName, $data, $metadata);

        $this->assertInstanceOf(DummyEnvelope::class, $envelope);
        $this->assertSame($handlerName, $envelope->getHandlerName());
        $this->assertSame($data, $envelope->getData());
        $this->assertArrayHasKey('meta', $envelope->getMetadata());
        $this->assertSame('data', $envelope->getMetadata()['meta']);
    }

    public function testNonArrayStackIsNormalized(): void
    {
        $base = new Message('handler', 'data', [EnvelopeInterface::ENVELOPE_STACK_KEY => 'oops']);
        $wrapped = new DummyEnvelope($base, 'id-1');

        $meta = $wrapped->getMetadata();
        self::assertIsArray($meta[EnvelopeInterface::ENVELOPE_STACK_KEY]);
        self::assertSame([DummyEnvelope::class], $meta[EnvelopeInterface::ENVELOPE_STACK_KEY]);
    }
}
