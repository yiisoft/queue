<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Serializer;

use JsonException;

use function is_array;
use function is_string;

use const JSON_THROW_ON_ERROR;

/**
 * Encodes and decodes queue messages using JSON format.
 */
final class JsonMessageEncoder implements MessageEncoderInterface
{
    public function encode(string $type, bool|int|float|string|array|null $data, array $metadata): string
    {
        try {
            return json_encode(
                [
                    'type' => $type,
                    'data' => $data,
                    'meta' => $metadata,
                ],
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            throw new MessageEncoderException($e->getMessage(), previous: $e);
        }
    }

    public function decode(string $value): array
    {
        try {
            $payload = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new MessageEncoderException($e->getMessage(), previous: $e);
        }

        if (!is_array($payload)) {
            throw new MessageEncoderException('Payload must be array. Got ' . get_debug_type($payload) . '.');
        }

        $type = $payload['type'] ?? null;
        if (!isset($type) || !is_string($type)) {
            throw new MessageEncoderException('Message type must be a string. Got ' . get_debug_type($type) . '.');
        }

        $meta = $payload['meta'] ?? [];
        if (!is_array($meta)) {
            throw new MessageEncoderException('Metadata must be an array. Got ' . get_debug_type($meta) . '.');
        }

        return [
            $type,
            $payload['data'] ?? null,
            $meta,
        ];
    }
}
