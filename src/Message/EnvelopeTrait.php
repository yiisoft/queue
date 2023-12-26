<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

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
        return $this->message->getMetadata();
    }
}
