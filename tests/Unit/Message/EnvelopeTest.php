<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Tests\App\DummyEnvelope;

final class EnvelopeTest extends TestCase
{
    public function testFromData(): void
    {
        $type = 'test-handler';
        $data = ['key' => 'value'];
        $metadata = ['meta' => 'data'];

        $envelope = DummyEnvelope::fromData($type, $data, $metadata);

        $this->assertInstanceOf(DummyEnvelope::class, $envelope);
        $this->assertSame($type, $envelope->getType());
        $this->assertSame($data, $envelope->getData());
        $this->assertArrayHasKey('meta', $envelope->getMetadata());
        $this->assertSame('data', $envelope->getMetadata()['meta']);
    }
}
