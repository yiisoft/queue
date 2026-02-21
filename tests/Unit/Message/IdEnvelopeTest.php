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

    public function testFromMessageWithStringId(): void
    {
        $id = 'test-id';
        $message = $this->createMessage([IdEnvelope::MESSAGE_ID_KEY => $id]);

        $envelope = IdEnvelope::fromMessage($message);

        $this->assertSame($id, $envelope->getId());
    }

    public function testFromMessageWithIntId(): void
    {
        $id = 123;
        $message = $this->createMessage([IdEnvelope::MESSAGE_ID_KEY => $id]);

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
        $message = $this->createMessage([IdEnvelope::MESSAGE_ID_KEY => $stringableObject]);
        $envelope = IdEnvelope::fromMessage($message);

        $this->assertSame('object-id', $envelope->getId());
    }

    public function testFromMessageWithInvalidIdType(): void
    {
        $invalidId = ['array-cannot-be-id'];
        $message = $this->createMessage([IdEnvelope::MESSAGE_ID_KEY => $invalidId]);
        $message = IdEnvelope::fromMessage($message);

        $this->assertNull($message->getId());
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

    public function testFromData(): void
    {
        $handlerName = 'test-handler';
        $data = ['key' => 'value'];
        $metadata = ['meta' => 'data', IdEnvelope::MESSAGE_ID_KEY => 'test-id'];

        $envelope = IdEnvelope::fromData($handlerName, $data, $metadata);

        $this->assertInstanceOf(IdEnvelope::class, $envelope);
        $this->assertSame($handlerName, $envelope->getHandlerName());
        $this->assertSame($data, $envelope->getData());
        $this->assertArrayHasKey('meta', $envelope->getMetadata());
        $this->assertSame('data', $envelope->getMetadata()['meta']);
        $this->assertSame('test-id', $envelope->getId());
    }

    private function createMessage(array $metadata = []): MessageInterface
    {
        return new Message('test-handler', ['test-data'], $metadata);
    }
}
