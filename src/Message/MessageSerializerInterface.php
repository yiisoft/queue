<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * Message serializer interface for converting messages to and from string representation.
 */
interface MessageSerializerInterface
{
    /**
     * Serialize a message to a string.
     *
     * Converts a message to its string representation for storage or transmission. The message passed to this method is
     * guaranteed to not be an {@see Envelope} instance, only a plain {@see MessageInterface} with merged metadata from
     * any wrapping envelopes.
     *
     * @param MessageInterface $message The message to serialize. Never an {@see Envelope} instance.
     * @return string The serialized message.
     */
    public function serialize(MessageInterface $message): string;

    /**
     * Unserialize a message from a string.
     *
     * Converts a string representation back to a {@see MessageInterface} instance.
     *
     * @param string $value The serialized message string.
     * @return MessageInterface The deserialized message.
     */
    public function unserialize(string $value): MessageInterface;
}
