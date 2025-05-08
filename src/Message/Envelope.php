<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

abstract class Envelope implements EnvelopeInterface
{
    public function __construct(protected MessageInterface $message)
    {
    }

    public static function fromData(string $handlerName, mixed $data, array $metadata = []): static
    {
        return static::fromMessage(Message::fromData($handlerName, $data, $metadata));
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function getHandlerName(): string
    {
        return $this->message->getHandlerName();
    }

    public function getData(): mixed
    {
        return $this->message->getData();
    }

    public function getMetadata(): array
    {
        return array_merge(
            $this->message->getMetadata(),
            [
                EnvelopeInterface::ENVELOPE_STACK_KEY => array_merge(
                    $this->message->getMetadata()[EnvelopeInterface::ENVELOPE_STACK_KEY] ?? [],
                    [static::class],
                ),
            ],
            $this->getEnvelopeMetadata(),
        );
    }

    abstract protected function getEnvelopeMetadata(): array;
}
