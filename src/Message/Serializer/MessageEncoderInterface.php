<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Serializer;

use Yiisoft\Queue\Message\MessageInterface;

/**
 * Encodes and decodes raw message parts (type, data, metadata) to and from a string.
 *
 * @psalm-import-type MessageData from MessageInterface
 * @psalm-import-type MessageMetadata from MessageInterface
 */
interface MessageEncoderInterface
{
    /**
     * Encodes a message into a string representation.
     *
     * @param string $type Message type.
     * @param bool|int|float|string|array|null $data Message payload data.
     * @param array $metadata Message metadata.
     *
     * @throws MessageEncoderException If encoding fails.
     *
     * @psalm-param MessageData $data
     * @psalm-param MessageMetadata $metadata
     */
    public function encode(string $type, bool|int|float|string|array|null $data, array $metadata): string;

    /**
     * Decodes a string representation back into message parts.
     *
     * @param string $value Encoded message string.
     *
     * @return array Tuple of type, data, and metadata.
     *
     * @throws MessageEncoderException If decoding fails.
     *
     * @psalm-return list{string, MessageData, MessageMetadata}
     */
    public function decode(string $value): array;
}
