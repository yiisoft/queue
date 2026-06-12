<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Serializer;

use JsonException;

use function is_array;

use const JSON_THROW_ON_ERROR;

/**
 * Encodes and decodes queue messages using JSON format.
 */
final class JsonMessageEncoder implements MessageEncoderInterface
{
    public function encode(array $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new MessageEncoderException($e->getMessage(), previous: $e);
        }
    }

    public function decode(string $value): mixed
    {
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new MessageEncoderException($e->getMessage(), previous: $e);
        }
    }
}
