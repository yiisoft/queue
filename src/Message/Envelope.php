<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

use function is_array;

abstract class Envelope implements EnvelopeInterface
{
    private readonly MessageInterface $message;

    /**
     * @psalm-var array<string, mixed>
     */
    private readonly array $metadata;

    public function __construct(MessageInterface $message, array $metadata)
    {
        $this->metadata = $this->prepareMetadata($message->getMetadata(), $metadata);

        while ($message instanceof self) {
            $message = $message->getMessage();
        }
        $this->message = $message;
    }

    /** @psalm-suppress MoreSpecificReturnType */
    public static function fromData(string $type, mixed $data, array $metadata = []): static
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return static::fromMessage(Message::fromData($type, $data, $metadata));
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function getType(): string
    {
        return $this->message->getType();
    }

    public function getData(): mixed
    {
        return $this->message->getData();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function prepareMetadata(array $messageMeta, array $metadata): array
    {
        $stack = $messageMeta[EnvelopeInterface::ENVELOPE_STACK_KEY] ?? [];
        if (!is_array($stack)) {
            $stack = [];
        }

        return array_merge(
            $messageMeta,
            [
                EnvelopeInterface::ENVELOPE_STACK_KEY => array_merge(
                    $stack,
                    [static::class],
                ),
            ],
            $metadata,
        );
    }
}
