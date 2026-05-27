<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;

final class IdEnvelopeTest extends TestCase
{
    public function testConstructor(): void
    {
        $message = $this->createMessage();
        $id = 'test-id';

        $envelope = new IdEnvelope($message, $id);

        $this->assertSame($message, $envelope->getMessage());
        $this->assertSame($id, $envelope->getId());
    }

    public function testGetEnvelopeMetadata(): void
    {
        $id = 'test-id';
        $message = $this->createMessage();
        $envelope = new IdEnvelope($message, $id);

        $metadata = $envelope->getMetadata();

        $this->assertArrayHasKey(IdEnvelope::MESSAGE_ID_KEY, $metadata);
        $this->assertSame($id, $metadata[IdEnvelope::MESSAGE_ID_KEY]);
    }

    private function createMessage(array $metadata = []): MessageInterface
    {
        return new Message('test-handler', ['test-data'], $metadata);
    }
}
