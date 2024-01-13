<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\JsonMessageSerializer;

/**
 * Testing message serialization options
 */
final class JsonMessageSerializerTest extends TestCase
{
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
        $payload = ['data' => 'test', 'meta' => $meta];
        $serializer = $this->createSerializer();

        $this->expectExceptionMessage(sprintf('Metadata must be array. Got %s.', get_debug_type($meta)));
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
        $payload = ['data' => 'test'];
        $serializer = $this->createSerializer();

        $message = $serializer->unserialize(json_encode($payload));

        $this->assertInstanceOf(MessageInterface::class, $message);
        $this->assertEquals($payload['data'], $message->getData());
        $this->assertEquals([], $message->getMetadata());
    }

    public function testUnserializeWithMetadata(): void
    {
        $payload = ['data' => 'test', 'meta' => ['int' => 1, 'str' => 'string', 'bool' => true]];
        $serializer = $this->createSerializer();

        $message = $serializer->unserialize(json_encode($payload));

        $this->assertInstanceOf(MessageInterface::class, $message);
        $this->assertEquals($payload['data'], $message->getData());
        $this->assertEquals(['int' => 1, 'str' => 'string', 'bool' => true], $message->getMetadata());
    }

    public function testUnserializeEnvelopeStack(): void
    {
        $payload = [
            'data' => 'test',
            'meta' => [
                EnvelopeInterface::ENVELOPE_STACK_KEY => [
                    IdEnvelope::class,
                ],
            ],
        ];
        $serializer = $this->createSerializer();

        $message = $serializer->unserialize(json_encode($payload));

        $this->assertInstanceOf(MessageInterface::class, $message);
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
            '{"name":"handler","data":"test","meta":[]}',
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
                '{"name":"handler","data":"test","meta":{"envelopes":["%s"],"%s":"test-id"}}',
                str_replace('\\', '\\\\', IdEnvelope::class),
                IdEnvelope::MESSAGE_ID_KEY,
            ),
            $json,
        );

        $message = $serializer->unserialize($json);

        $this->assertInstanceOf(IdEnvelope::class, $message);
        $this->assertEquals('test-id', $message->getId());
    }

    private function createSerializer(): JsonMessageSerializer
    {
        return new JsonMessageSerializer();
    }
}
