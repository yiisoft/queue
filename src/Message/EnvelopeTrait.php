<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

trait EnvelopeTrait
{
    private MessageInterface $message;
    private ?EnvelopeStack $stack = null;

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
        $envelope = new static($message);
        $envelope->getStack()->add($envelope);

        return $envelope;
    }

    public function getMetadata(): array
    {
        return $this->message->getMetadata();
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

    public function getStack(): EnvelopeStack
    {
        $stack = $this->stack;
        if (isset($this->stack)) {
            $stack= $this->stack;
        }
        if ($this->message instanceof EnvelopeInterface) {
            $stack = $this->message->getStack();
        }
        return $this->stack ??= $stack ?? new EnvelopeStack();
    }

    public function withStack(EnvelopeStack $stack): void
    {
        $this->stack = $stack;
    }
}
