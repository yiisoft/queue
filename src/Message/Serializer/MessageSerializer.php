<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Serializer;

use Yiisoft\Queue\Message\Envelope;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;

use function is_array;
use function is_string;

/**
 * Serializes and unserializes queue messages, preserving the original message class in metadata.
 *
 * Delegates the wire format to {@see MessageEncoderInterface}. When unserializing, restores the original message class
 * from metadata, falling back to {@see GenericMessage} if the class is missing or invalid.
 */
final class MessageSerializer
{
    private const META_MESSAGE_CLASS = 'message-class';

    public function __construct(
        private readonly MessageEncoderInterface $encoder,
    ) {}

    /**
     * Serializes a message to a string.
     *
     * @param MessageInterface $message Message to serialize.
     *
     * @throws MessageEncoderException If encoding fails.
     */
    public function serialize(MessageInterface $message): string
    {
        $metadata = $message->getMetadata();

        if (!isset($metadata[self::META_MESSAGE_CLASS])) {
            $metadata[self::META_MESSAGE_CLASS] = $message instanceof Envelope
                ? $message->getMessage()::class
                : $message::class;
        }

        return $this->encoder->encode([
            'type' => $message->getType(),
            'data' => $message->getData(),
            'meta' => $metadata,
        ]);
    }

    /**
     * Unserializes a message from a string.
     *
     * @param string $value Encoded message string.
     *
     * @throws MessageEncoderException If decoding fails or the decoded payload has an invalid format.
     */
    public function unserialize(string $value): MessageInterface
    {
        $data = $this->encoder->decode($value);

        if (!is_array($data)) {
            throw new MessageEncoderException('Decoded data must be array. Got ' . get_debug_type($data) . '.');
        }

        $type = $data['type'] ?? null;
        if (!isset($type) || !is_string($type)) {
            throw new MessageEncoderException('Message type must be a string. Got ' . get_debug_type($type) . '.');
        }

        $metadata = $data['meta'] ?? [];
        if (!is_array($metadata)) {
            throw new MessageEncoderException('Metadata must be an array. Got ' . get_debug_type($metadata) . '.');
        }

        $class = $metadata[self::META_MESSAGE_CLASS] ?? GenericMessage::class;

        // Don't check subclasses when it's a default class: that's faster
        if ($class !== GenericMessage::class
            && (!is_string($class) || !is_subclass_of($class, MessageInterface::class))
        ) {
            $class = GenericMessage::class;
        }
        /** @var class-string<MessageInterface> $class */

        return $class::fromData($type, $data['data'] ?? null)->withMetadata($metadata);
    }
}
