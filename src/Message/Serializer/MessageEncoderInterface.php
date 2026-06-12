<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Serializer;

/**
 * Encodes and decodes a data array to and from a string.
 */
interface MessageEncoderInterface
{
    /**
     * Encodes a data array into a string representation.
     *
     * @param array $data Data to encode. Contains only scalars, nulls, and arrays — no objects or resources.
     *
     * @throws MessageSerializerException If encoding fails.
     */
    public function encode(array $data): string;

    /**
     * Decodes a string representation back into a value.
     *
     * @param string $value Encoded string.
     *
     * @return mixed Decoded data.
     *
     * @throws MessageSerializerException If decoding fails.
     */
    public function decode(string $value): mixed;
}
