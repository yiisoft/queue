<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

trait EnvelopeTrait
{
    private MessageInterface $message;

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function withMessage(MessageInterface $message): self
    {
        $instance = clone $this;
        $instance->message = $message;

        return $instance;
    }

    /**
     * @return class-string<MessageHandlerInterface>
     */
    public function getHandler(): string
    {
        return $this->message->getHandler();
    }

    public function getData(): mixed
    {
        return $this->message->getData();
    }

    public function getMetadata(): array
    {
        return $this->message->getMetadata();
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
