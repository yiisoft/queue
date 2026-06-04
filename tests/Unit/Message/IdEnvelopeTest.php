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

    public function testFromMessageWithObjectHavingToString(): void
    {
        $stringableObject = new class {
            public function __toString(): string
            {
                return 'object-id';
            }
        };
        $message = $this->createMessage([IdEnvelope::META_ID => $stringableObject]);
        $envelope = IdEnvelope::fromMessage($message);

        $this->assertSame('object-id', $envelope->getId());
    }

    public function testFromMessageWithInvalidIdType(): void
    {
        $invalidId = ['array-cannot-be-id'];
        $message = $this->createMessage([IdEnvelope::META_ID => $invalidId]);
        $message = IdEnvelope::fromMessage($message);

        $this->assertNull($message->getId());
    }

    public function testGetEnvelopeMetadata(): void
    {
        $id = 'test-id';
        $message = $this->createMessage();
        $envelope = new IdEnvelope($message, $id);

        $metadata = $envelope->getMetadata();

        $this->assertArrayHasKey(IdEnvelope::META_ID, $metadata);
        $this->assertSame($id, $metadata[IdEnvelope::META_ID]);
    }

    private function createMessage(array $metadata = []): MessageInterface
    {
        return (new GenericMessage('test-handler', ['test-data']))->withMetadata($metadata);
    }
}
