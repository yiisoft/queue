<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Serializer;

use Yiisoft\Queue\Message\MessageInterface;

/**
 * Serializes and unserializes queue messages to and from a string representation.
 */
interface MessageSerializerInterface
{
    /**
     * Serializes a message to a string.
     *
     * @param MessageInterface $message Message to serialize.
     *
     * @throws MessageSerializerException If encoding fails.
     */
    public function serialize(MessageInterface $message): string;

    /**
     * Unserializes a message from a string.
     *
     * @param string $value Encoded message string.
     *
     * @throws MessageSerializerException If decoding fails or the decoded payload has an invalid format.
     */
    public function unserialize(string $value): MessageInterface;
}
