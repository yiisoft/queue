<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message\Serializer;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\Serializer\JsonMessageEncoder;
use Yiisoft\Queue\Message\Serializer\MessageEncoderException;

use function sprintf;

use const JSON_THROW_ON_ERROR;

final class JsonMessageSerializerTest extends TestCase
{
    #[TestWith([''])]
    #[TestWith([1])]
    #[TestWith([true])]
    #[TestWith([null])]
    public function testUnsupportedValue(mixed $raw): void
    {
        $encoder = new JsonMessageEncoder();
        $value = json_encode($raw, JSON_THROW_ON_ERROR);

        $this->expectException(MessageEncoderException::class);
        $this->expectExceptionMessage(sprintf('Payload must be array. Got %s.', get_debug_type($raw)));
        $encoder->decode($value);
    }

    #[TestWith([1])]
    #[TestWith([true])]
    #[TestWith([null])]
    #[TestWith([[]])]
    public function testUnsupportedType(mixed $type): void
    {
        $encoder = new JsonMessageEncoder();
        $value = json_encode(
            ['type' => $type, 'data' => 'test', 'meta' => []],
            JSON_THROW_ON_ERROR,
        );

        $this->expectException(MessageEncoderException::class);
        $this->expectExceptionMessage(sprintf('Message type must be a string. Got %s.', get_debug_type($type)));
        $encoder->decode($value);
    }

    #[TestWith([''])]
    #[TestWith([1])]
    #[TestWith([true])]
    public function testUnsupportedMetadata(mixed $metadata): void
    {
        $encoder = new JsonMessageEncoder();
        $value = json_encode(
            ['type' => 'test', 'data' => 'test', 'meta' => $metadata],
            JSON_THROW_ON_ERROR,
        );

        $this->expectException(MessageEncoderException::class);
        $this->expectExceptionMessage(sprintf('Metadata must be an array. Got %s.', get_debug_type($metadata)));
        $encoder->decode($value);
    }
}
