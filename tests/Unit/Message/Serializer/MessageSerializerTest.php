<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message\Serializer;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Serializer\JsonMessageEncoder;
use Yiisoft\Queue\Message\Serializer\MessageSerializerException;
use Yiisoft\Queue\Message\Serializer\MessageSerializer;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Tests\Unit\Support\TestMessage;

use function sprintf;

use const JSON_THROW_ON_ERROR;

final class MessageSerializerTest extends TestCase
{
    #[TestWith(['"string"', 'string'])]
    #[TestWith(['42', 'int'])]
    #[TestWith(['true', 'bool'])]
    #[TestWith(['null', 'null'])]
    public function testNonArrayPayload(string $json, string $type): void
    {
        $this->expectException(MessageSerializerException::class);
        $this->expectExceptionMessage(sprintf('Decoded data must be array. Got %s.', $type));
        $this->createSerializer()->unserialize($json);
    }

    #[TestWith([1])]
    #[TestWith([true])]
    #[TestWith([null])]
    #[TestWith([[]])]
    public function testUnsupportedType(mixed $type): void
    {
        $value = json_encode(
            ['type' => $type, 'data' => 'test', 'meta' => []],
            JSON_THROW_ON_ERROR,
        );

        $this->expectException(MessageSerializerException::class);
        $this->expectExceptionMessage(sprintf('Message type must be a string. Got %s.', get_debug_type($type)));
        $this->createSerializer()->unserialize($value);
    }

    #[TestWith([''])]
    #[TestWith([1])]
    #[TestWith([true])]
    public function testUnsupportedMetadata(mixed $metadata): void
    {
        $value = json_encode(
            ['type' => 'test', 'data' => 'test', 'meta' => $metadata],
            JSON_THROW_ON_ERROR,
        );

        $this->expectException(MessageSerializerException::class);
        $this->expectExceptionMessage(sprintf('Metadata must be an array. Got %s.', get_debug_type($metadata)));
        $this->createSerializer()->unserialize($value);
    }

    public function testFallbackToGenericMessageForUnknownType(): void
    {
        $payload = [
            'type' => 'handler',
            'data' => 'test',
            'meta' => [],
        ];

        $message = $this->createSerializer()->unserialize(json_encode($payload));

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
            '{"type":"handler","data":"test","meta":[]}',
            $json,
        );
    }

    public function testSerializeEnvelopeStack(): void
    {
        $message = new IdEnvelope(new GenericMessage('handler', 'test'), 'test-id');
        $serializer = $this->createSerializer();

        $json = $serializer->serialize($message);

        $this->assertEquals(
            sprintf('{"type":"handler","data":"test","meta":{"%s":"test-id"}}', IdEnvelope::META_ID),
            $json,
        );

        $restored = $serializer->unserialize($json);

        $this->assertInstanceOf(GenericMessage::class, $restored);
        $this->assertEquals([
            IdEnvelope::META_ID => 'test-id',
        ], $restored->getMetadata());
    }

    public function testRestoreOriginalMessageClass(): void
    {
        $message = new TestMessage();
        $serializer = $this->createSerializer(['test' => TestMessage::class]);

        $restored = $serializer->unserialize($serializer->serialize($message));

        $this->assertInstanceOf(TestMessage::class, $restored);
    }

    public function testRestoreOriginalMessageClassWithEnvelope(): void
    {
        $message = new IdEnvelope(new TestMessage(), 1);
        $serializer = $this->createSerializer(['test' => TestMessage::class]);

        $restored = $serializer->unserialize($serializer->serialize($message));

        $this->assertInstanceOf(TestMessage::class, $restored);
    }

    private function createSerializer(array $classResolver = []): MessageSerializer
    {
        return new MessageSerializer(new JsonMessageEncoder(), $classResolver);
    }
}
