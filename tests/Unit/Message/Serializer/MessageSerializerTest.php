<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message\Serializer;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Serializer\JsonMessageEncoder;
use Yiisoft\Queue\Message\Serializer\MessageSerializer;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Tests\Unit\Support\TestMessage;

use function sprintf;

use const JSON_THROW_ON_ERROR;

final class MessageSerializerTest extends TestCase
{
    public function testDefaultMessageClassFallbackWrongClass(): void
    {
        $payload = [
            'type' => 'handler',
            'data' => 'test',
            'meta' => [
                'message-class' => 'NonExistentClass',
            ],
        ];

        $message = $this->createSerializer()->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertInstanceOf(GenericMessage::class, $message);
    }

    public function testDefaultMessageClassFallbackClassNotSet(): void
    {
        $payload = [
            'type' => 'handler',
            'data' => 'test',
            'meta' => [],
        ];

        $message = $this->createSerializer()->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertInstanceOf(GenericMessage::class, $message);
    }

    public function testUnserializeFromData(): void
    {
        $payload = ['type' => 'handler', 'data' => 'test'];

        $message = $this->createSerializer()->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertEquals($payload['data'], $message->getData());
        $this->assertEquals([], $message->getMetadata());
    }

    public function testUnserializeWithMetadata(): void
    {
        $payload = ['type' => 'handler', 'data' => 'test', 'meta' => ['int' => 1, 'str' => 'string', 'bool' => true]];

        $message = $this->createSerializer()->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertEquals($payload['data'], $message->getData());
        $this->assertEquals(['int' => 1, 'str' => 'string', 'bool' => true], $message->getMetadata());
    }

    public function testSerialize(): void
    {
        $message = new GenericMessage('handler', 'test');

        $json = $this->createSerializer()->serialize($message);

        $this->assertEquals(
            '{"type":"handler","data":"test","meta":{"message-class":"Yiisoft\\\\Queue\\\\Message\\\\GenericMessage"}}',
            $json,
        );
    }

    public function testSerializeEnvelopeStack(): void
    {
        $message = new IdEnvelope(new GenericMessage('handler', 'test'), 'test-id');
        $serializer = $this->createSerializer();

        $json = $serializer->serialize($message);

        $this->assertEquals(
            sprintf(
                '{"type":"handler","data":"test","meta":{"%s":"test-id","message-class":"%s"}}',
                IdEnvelope::META_ID,
                str_replace('\\', '\\\\', GenericMessage::class),
            ),
            $json,
        );

        $restored = $serializer->unserialize($json);

        $this->assertInstanceOf(GenericMessage::class, $restored);
        $this->assertEquals([
            IdEnvelope::META_ID => 'test-id',
            'message-class' => GenericMessage::class,
        ], $restored->getMetadata());
    }

    public function testRestoreOriginalMessageClass(): void
    {
        $message = new TestMessage();
        $serializer = $this->createSerializer();

        $restored = $serializer->unserialize($serializer->serialize($message));

        $this->assertInstanceOf(TestMessage::class, $restored);
    }

    public function testRestoreOriginalMessageClassWithEnvelope(): void
    {
        $message = new IdEnvelope(new TestMessage(), 1);
        $serializer = $this->createSerializer();

        $restored = $serializer->unserialize($serializer->serialize($message));

        $this->assertInstanceOf(TestMessage::class, $restored);
    }

    private function createSerializer(): MessageSerializer
    {
        return new MessageSerializer(new JsonMessageEncoder());
    }
}
