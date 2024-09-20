<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

use InvalidArgumentException;
use JsonException;

final class JsonMessageSerializer implements MessageSerializerInterface
{
    /**
     * @throws JsonException
     */
    public function serialize(MessageInterface $message): string
    {
        $payload = [
            'name' => $message->getHandlerName(),
            'data' => $message->getData(),
            'meta' => $message->getMetadata(),
        ];

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public function unserialize(string $value): MessageInterface
    {
        $payload = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new InvalidArgumentException('Payload must be array. Got ' . get_debug_type($payload) . '.');
        }

        $meta = $payload['meta'] ?? [];
        if (!is_array($meta)) {
            throw new InvalidArgumentException('Metadata must be array. Got ' . get_debug_type($meta) . '.');
        }

        $envelopes = [];
        if (isset($meta[EnvelopeInterface::ENVELOPE_STACK_KEY]) && is_array($meta[EnvelopeInterface::ENVELOPE_STACK_KEY])) {
            $envelopes = $meta[EnvelopeInterface::ENVELOPE_STACK_KEY];
        }
        $meta[EnvelopeInterface::ENVELOPE_STACK_KEY] = [];

        // TODO: will be removed later
        $message = new Message($payload['name'] ?? '$name', $payload['data'] ?? null, $meta);

        foreach ($envelopes as $envelope) {
            if (is_string($envelope) && class_exists($envelope) && is_subclass_of(
                    $envelope,
                    EnvelopeInterface::class
                )) {
                $message = $envelope::fromMessage($message);
            }
        }

        return $message;
    }
}
