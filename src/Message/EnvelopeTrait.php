<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

trait EnvelopeTrait
{
    private MessageInterface $message;

    public function getMessage(): MessageInterface
    {
        $message = $this->message;
        while ($message instanceof EnvelopeInterface) {
            $message = $message->getMessage();
        }
        return $message;
    }

    public function withMessage(MessageInterface $message): self
    {
        $instance = clone $this;
        $instance->message = $message;

        return $instance;
    }

    public function getData(): mixed
    {
        return $this->message->getData();
    }

    public static function fromMessage(MessageInterface $message): EnvelopeInterface
    {
        return new static($message);
    }

    public function getMetadata(): array
    {
        return array_merge(
            $this->message->getMetadata(),
            [
                self::ENVELOPE_STACK_KEY => array_merge(
                    $this->message->getMetadata()[self::ENVELOPE_STACK_KEY] ?? [],
                    [self::class],
                ),
            ],
            $this->getEnvelopeMetadata(),
        );
    }

    public function getEnvelopeMetadata(): array
    {
        return [];
    }

    public function withData(mixed $data): self
    {
        $instance = clone $this;
        $instance->message = $instance->message->withData($data);

        return $instance;
    }

    public function withMetadata(array $metadata): self
    {
        $instance = clone $this;
        $instance->message = $instance->message->withMetadata($metadata);

        return $instance;
    }
}
