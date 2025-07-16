<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Tests\App\DummyEnvelope;

final class EnvelopeTraitTest extends TestCase
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
}
