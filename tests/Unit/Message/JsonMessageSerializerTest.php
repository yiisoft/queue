<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Tests\Unit\Support\TestMessage;

use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Testing message serialization options
 */
final class JsonMessageSerializerTest extends TestCase
{
    /**
     * @dataProvider dataUnsupportedTypeFormat
     */
    #[DataProvider('dataUnsupportedTypeFormat')]
    public function testTypeFormat(mixed $type): void
    {
        $payload = ['type' => $type, 'data' => 'test'];
        $serializer = $this->createSerializer();

        $this->expectExceptionMessage(sprintf('Message type must be a string. Got %s.', get_debug_type($type)));
        $this->expectException(InvalidArgumentException::class);
        $serializer->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public static function dataUnsupportedTypeFormat(): iterable
    {
        yield 'number' => [1];
        yield 'boolean' => [true];
        yield 'null' => [null];
        yield 'array' => [[]];
    }

    public function testDefaultMessageClassFallbackWrongClass(): void
    {
        $serializer = $this->createSerializer();
        $payload = [
            'type' => 'handler',
            'data' => 'test',
            'meta' => [
                'message-class' => 'NonExistentClass',
            ],
        ];

        $message = $serializer->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertInstanceOf(Message::class, $message);
    }

    public function testDefaultMessageClassFallbackClassNotSet(): void
    {
        $serializer = $this->createSerializer();
        $payload = [
            'type' => 'handler',
            'data' => 'test',
            'meta' => [],
        ];
        $message = $serializer->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertInstanceOf(Message::class, $message);
    }

    #[DataProvider('dataUnsupportedPayloadFormat')]
    public function testPayloadFormat(mixed $payload): void
    {
        $serializer = $this->createSerializer();

        $this->expectExceptionMessage(sprintf('Payload must be array. Got %s.', get_debug_type($payload)));
        $this->expectException(InvalidArgumentException::class);
        $serializer->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public static function dataUnsupportedPayloadFormat(): iterable
    {
        yield 'string' => [''];
        yield 'number' => [1];
        yield 'boolean' => [true];
        yield 'null' => [null];
    }

    #[DataProvider('dataUnsupportedMetadataFormat')]
    public function testMetadataFormat(mixed $meta): void
    {
        $payload = ['type' => 'handler', 'data' => 'test', 'meta' => $meta];
        $serializer = $this->createSerializer();

        $this->expectExceptionMessage(sprintf('Metadata must be an array. Got %s.', get_debug_type($meta)));
        $this->expectException(InvalidArgumentException::class);
        $serializer->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public static function dataUnsupportedMetadataFormat(): iterable
    {
        yield 'string' => [''];
        yield 'number' => [1];
        yield 'boolean' => [true];
    }

    public function testUnserializeFromData(): void
    {
        $payload = ['type' => 'handler', 'data' => 'test'];
        $serializer = $this->createSerializer();

        $message = $serializer->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertEquals($payload['data'], $message->getData());
        $this->assertEquals([], $message->getMetadata());
    }

    public function testUnserializeWithMetadata(): void
    {
        $payload = ['type' => 'handler', 'data' => 'test', 'meta' => ['int' => 1, 'str' => 'string', 'bool' => true]];
        $serializer = $this->createSerializer();

        $message = $serializer->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertEquals($payload['data'], $message->getData());
        $this->assertEquals(['int' => 1, 'str' => 'string', 'bool' => true], $message->getMetadata());
    }

    public function testUnserializeEnvelopeStack(): void
    {
        $payload = [
            'type' => 'handler',
            'data' => 'test',
            'meta' => [],
        ];
        $serializer = $this->createSerializer();

        /** @var IdEnvelope $message */
        $message = $serializer->unserialize(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertEquals($payload['data'], $message->getData());
        $this->assertInstanceOf(Message::class, $message);
    }

    public function testSerialize(): void
    {
        $message = new Message('handler', 'test');

        $serializer = $this->createSerializer();

        $json = $serializer->serialize($message);

        $this->assertEquals(
            '{"type":"handler","data":"test","meta":{"message-class":"Yiisoft\\\\Queue\\\\Message\\\\Message"}}',
            $json,
        );
    }

    public function testSerializeEnvelopeStack(): void
    {
        $message = new Message('handler', 'test');
        $message = new IdEnvelope($message, 'test-id');

        $serializer = $this->createSerializer();

        $json = $serializer->serialize($message);

        $this->assertEquals(
            sprintf(
                '{"type":"handler","data":"test","meta":{"%s":"test-id","message-class":"%s"}}',
                IdEnvelope::MESSAGE_ID_KEY,
                str_replace('\\', '\\\\', Message::class),
            ),
            $json,
        );

        $message = $serializer->unserialize($json);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals([
            IdEnvelope::MESSAGE_ID_KEY => 'test-id',
            'message-class' => Message::class,
        ], $message->getMetadata());
    }

    public function testRestoreOriginalMessageClass(): void
    {
        $message = new TestMessage();
        $serializer = $this->createSerializer();
        $serializer->unserialize($serializer->serialize($message));

        $this->assertInstanceOf(TestMessage::class, $message);
    }

    public function testRestoreOriginalMessageClassWithEnvelope(): void
    {
        $message = new IdEnvelope(new TestMessage(), 1);
        $serializer = $this->createSerializer();
        $serializer->unserialize($serializer->serialize($message));

        $this->assertInstanceOf(IdEnvelope::class, $message);
        $this->assertInstanceOf(TestMessage::class, $message->getMessage());
    }

    private function createSerializer(): JsonMessageSerializer
    {
        return new JsonMessageSerializer();
    }
}
