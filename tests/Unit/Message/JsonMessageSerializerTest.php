<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Tests\Unit\Support\TestMessage;

/**
 * Testing message serialization options
 */
final class JsonMessageSerializerTest extends TestCase
{
    /**
     * @dataProvider dataUnsupportedHandlerNameFormat
     */
    #[DataProvider('dataUnsupportedHandlerNameFormat')]
    public function testHandlerNameFormat(mixed $name): void
    {
        $payload = ['name' => $name, 'data' => 'test'];
        $serializer = $this->createSerializer();

        $this->expectExceptionMessage(sprintf('Handler name must be a string. Got %s.', get_debug_type($name)));
        $this->expectException(InvalidArgumentException::class);
        $serializer->unserialize(json_encode($payload));
    }

    public static function dataUnsupportedHandlerNameFormat(): iterable
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
            'name' => 'handler',
            'data' => 'test',
            'meta' => [
                'message-class' => 'NonExistentClass',
            ],
        ];

        $message = $serializer->unserialize(json_encode($payload));
        $this->assertInstanceOf(Message::class, $message);
    }

    public function testDefaultMessageClassFallbackClassNotSet(): void
    {
        $serializer = $this->createSerializer();
        $payload = [
            'name' => 'handler',
            'data' => 'test',
            'meta' => [],
        ];
        $message = $serializer->unserialize(json_encode($payload));
        $this->assertInstanceOf(Message::class, $message);
    }

    /**
     * @dataProvider dataUnsupportedPayloadFormat
     */
    public function testPayloadFormat(mixed $payload): void
    {
        $serializer = $this->createSerializer();

        $this->expectExceptionMessage(sprintf('Payload must be array. Got %s.', get_debug_type($payload)));
        $this->expectException(InvalidArgumentException::class);
        $serializer->unserialize(json_encode($payload));
    }

    public static function dataUnsupportedPayloadFormat(): iterable
    {
        yield 'string' => [''];
        yield 'number' => [1];
        yield 'boolean' => [true];
        yield 'null' => [null];
    }

    /**
     * @dataProvider dataUnsupportedMetadataFormat
     */
    public function testMetadataFormat(mixed $meta): void
    {
        $payload = ['name' => 'handler', 'data' => 'test', 'meta' => $meta];
        $serializer = $this->createSerializer();

        $this->expectExceptionMessage(sprintf('Metadata must be an array. Got %s.', get_debug_type($meta)));
        $this->expectException(InvalidArgumentException::class);
        $serializer->unserialize(json_encode($payload));
    }

    public static function dataUnsupportedMetadataFormat(): iterable
    {
        yield 'string' => [''];
        yield 'number' => [1];
        yield 'boolean' => [true];
    }

    public function testUnserializeFromData(): void
    {
        $payload = ['name' => 'handler', 'data' => 'test'];
        $serializer = $this->createSerializer();

        $message = $serializer->unserialize(json_encode($payload));

        $this->assertEquals($payload['data'], $message->getData());
        $this->assertEquals([EnvelopeInterface::ENVELOPE_STACK_KEY => []], $message->getMetadata());
    }

    public function testUnserializeWithMetadata(): void
    {
        $payload = ['name' => 'handler', 'data' => 'test', 'meta' => ['int' => 1, 'str' => 'string', 'bool' => true]];
        $serializer = $this->createSerializer();

        $message = $serializer->unserialize(json_encode($payload));

        $this->assertEquals($payload['data'], $message->getData());
        $this->assertEquals(['int' => 1, 'str' => 'string', 'bool' => true, EnvelopeInterface::ENVELOPE_STACK_KEY => []], $message->getMetadata());
    }

    public function testUnserializeEnvelopeStack(): void
    {
        $payload = [
            'name' => 'handler',
            'data' => 'test',
            'meta' => [
                EnvelopeInterface::ENVELOPE_STACK_KEY => [
                    IdEnvelope::class,
                ],
            ],
        ];
        $serializer = $this->createSerializer();

        $message = $serializer->unserialize(json_encode($payload));

        $this->assertEquals($payload['data'], $message->getData());
        $this->assertEquals([IdEnvelope::class], $message->getMetadata()[EnvelopeInterface::ENVELOPE_STACK_KEY]);

        $this->assertInstanceOf(IdEnvelope::class, $message);
        $this->assertNull($message->getId());
        $this->assertInstanceOf(Message::class, $message->getMessage());
    }

    public function testSerialize(): void
    {
        $message = new Message('handler', 'test');

        $serializer = $this->createSerializer();

        $json = $serializer->serialize($message);

        $this->assertEquals(
            '{"name":"handler","data":"test","meta":{"message-class":"Yiisoft\\\\Queue\\\\Message\\\\Message"}}',
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
                '{"name":"handler","data":"test","meta":{"envelopes":["%s"],"%s":"test-id","message-class":"%s"}}',
                str_replace('\\', '\\\\', IdEnvelope::class),
                IdEnvelope::MESSAGE_ID_KEY,
                str_replace('\\', '\\\\', Message::class),
            ),
            $json,
        );

        $message = $serializer->unserialize($json);

        $this->assertInstanceOf(IdEnvelope::class, $message);
        $this->assertEquals('test-id', $message->getId());
        $this->assertEquals([
            EnvelopeInterface::ENVELOPE_STACK_KEY => [
                IdEnvelope::class,
            ],
            IdEnvelope::MESSAGE_ID_KEY => 'test-id',
            'message-class' => Message::class,
        ], $message->getMetadata());

        $this->assertEquals([
            EnvelopeInterface::ENVELOPE_STACK_KEY => [],
            IdEnvelope::MESSAGE_ID_KEY => 'test-id',
            'message-class' => Message::class,
        ], $message->getMessage()->getMetadata());
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
        $message = new IdEnvelope(new TestMessage());
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
