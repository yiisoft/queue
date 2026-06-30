<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\GenericMessage;
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

    public function testFromMessageWithStringId(): void
    {
        $id = 'test-id';
        $message = $this->createMessage([IdEnvelope::META_ID => $id]);

        $envelope = IdEnvelope::fromMessage($message);

        $this->assertSame($id, $envelope->getId());
    }

    public function testFromMessageWithIntId(): void
    {
        $id = 123;
        $message = $this->createMessage([IdEnvelope::META_ID => $id]);

        $envelope = IdEnvelope::fromMessage($message);

        $this->assertSame($id, $envelope->getId());
    }

    public function testFromMessageWithNullId(): void
    {
        $message = $this->createMessage();

        $envelope = IdEnvelope::fromMessage($message);

        $this->assertNull($envelope->getId());
    }

    public function testFromMessageWithInvalidIdType(): void
    {
        $invalidId = ['array-cannot-be-id'];
        $message = $this->createMessage([IdEnvelope::META_ID => $invalidId]);
        $message = IdEnvelope::fromMessage($message);

        $this->assertNull($message->getId());
    }

    public function testGetEnvelopeMeta(): void
    {
        $id = 'test-id';
        $message = $this->createMessage();
        $envelope = new IdEnvelope($message, $id);

        $meta = $envelope->getMeta();

        $this->assertArrayHasKey(IdEnvelope::META_ID, $meta);
        $this->assertSame($id, $meta[IdEnvelope::META_ID]);
    }

    private function createMessage(array $meta = []): MessageInterface
    {
        return (new GenericMessage('test-handler', ['test-data']))->withMeta($meta);
    }
}
