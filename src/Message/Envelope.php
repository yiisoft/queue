<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

use function is_array;

abstract class Envelope implements EnvelopeInterface
{
    private ?array $metadata = null;

    public function __construct(protected MessageInterface $message) {}

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
        if ($this->metadata === null) {
            $messageMeta = $this->message->getMetadata();

            $stack = $messageMeta[EnvelopeInterface::ENVELOPE_STACK_KEY] ?? [];
            if (!is_array($stack)) {
                $stack = [];
            }

            $this->metadata = array_merge(
                $messageMeta,
                [
                    EnvelopeInterface::ENVELOPE_STACK_KEY => array_merge(
                        $stack,
                        [static::class],
                    ),
                ],
                $this->getEnvelopeMetadata(),
            );
        }

        return $this->metadata;
    }

    abstract protected function getEnvelopeMetadata(): array;
}
