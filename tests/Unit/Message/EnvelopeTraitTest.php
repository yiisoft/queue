<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Tests\App\DummyEnvelope;

final class EnvelopeTraitTest extends TestCase
{
    private function createTestEnvelope(): DummyEnvelope
    {
        return new DummyEnvelope();
    }

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

    public function testWithMessage(): void
    {
        $originalMessage = new Message('original-handler', 'original-data');
        $newMessage = new Message('new-handler', 'new-data');

        $envelope = $this->createTestEnvelope();
        $envelope = $envelope->withMessage($originalMessage);

        $this->assertSame($originalMessage, $envelope->getMessage());

        $newEnvelope = $envelope->withMessage($newMessage);

        $this->assertNotSame($envelope, $newEnvelope);
        $this->assertSame($newMessage, $newEnvelope->getMessage());
        $this->assertSame($originalMessage, $envelope->getMessage());
    }
}
