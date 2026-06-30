<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Serializer;

use Yiisoft\Queue\Message\ClassResolver\ArrayMessageClassResolver;
use Yiisoft\Queue\Message\ClassResolver\MessageClassResolverInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;

use function is_array;
use function is_string;

/**
 * Serializes and unserializes queue messages, resolving the message class via a {@see MessageClassResolverInterface}.
 *
 * When serializing, assembles an array with `type`, `payload`, and `meta` keys and passes it as a single array to
 * {@see MessageEncoderInterface}, which encodes it to a string. When unserializing, decodes the string back to an
 * array and resolves the message class from the type via the resolver, falling back to {@see GenericMessage}
 * if the type is not registered.
 */
final class MessageSerializer implements MessageSerializerInterface
{
    private readonly MessageClassResolverInterface $resolver;

    /**
     * @param MessageEncoderInterface $encoder Encoder used to encode and decode message data.
     * @param MessageClassResolverInterface|array $classResolver Resolver for message classes, or a map of type to
     * class.
     *
     * @psalm-param MessageClassResolverInterface|array<string, class-string<MessageInterface>> $classResolver
     */
    public function __construct(
        private readonly MessageEncoderInterface $encoder,
        MessageClassResolverInterface|array $classResolver = [],
    ) {
        $this->resolver = is_array($classResolver)
            ? new ArrayMessageClassResolver($classResolver)
            : $classResolver;
    }

    public function serialize(MessageInterface $message): string
    {
        return $this->encoder->encode([
            'type' => $message->getType(),
            'payload' => $message->getPayload(),
            'meta' => $message->getMeta(),
        ]);
    }

    public function unserialize(string $value): MessageInterface
    {
        $data = $this->encoder->decode($value);

        if (!is_array($data)) {
            throw new MessageSerializerException('Decoded data must be array. Got ' . get_debug_type($data) . '.');
        }

        $type = $data['type'] ?? null;
        if (!isset($type) || !is_string($type)) {
            throw new MessageSerializerException('Message type must be a string. Got ' . get_debug_type($type) . '.');
        }

        $meta = $data['meta'] ?? [];
        if (!is_array($meta)) {
            throw new MessageSerializerException('Metadata must be an array. Got ' . get_debug_type($meta) . '.');
        }

        $class = $this->resolver->resolve($type) ?? GenericMessage::class;

        return $class::fromPayload($type, $data['payload'] ?? null)->withMeta($meta);
    }
}
