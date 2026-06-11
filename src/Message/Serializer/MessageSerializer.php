<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Serializer;

use Yiisoft\Queue\Message\Envelope;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;

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

        return $this->encoder->encode($message->getType(), $message->getData(), $metadata);
    }

    /**
     * Unserializes a message from a string.
     *
     * @param string $value Encoded message string.
     *
     * @throws MessageEncoderException If decoding fails.
     */
    public function unserialize(string $value): MessageInterface
    {
        [$type, $data, $metadata] = $this->encoder->decode($value);

        $class = $metadata[self::META_MESSAGE_CLASS] ?? GenericMessage::class;

        // Don't check subclasses when it's a default class: that's faster
        if ($class !== GenericMessage::class
            && (!is_string($class) || !is_subclass_of($class, MessageInterface::class))
        ) {
            $class = GenericMessage::class;
        }
        /** @var class-string<MessageInterface> $class */

        return $class::fromData($type, $data)->withMetadata($metadata);
    }
}
