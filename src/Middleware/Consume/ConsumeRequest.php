<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

use Yiisoft\Queue\Message\MessageInterface;

final class ConsumeRequest
{
    public function __construct(private MessageInterface $message, private string $queueName) {}

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    /** Logical name of the queue currently executing this message. */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function withMessage(MessageInterface $message): self
    {
        $instance = clone $this;
        $instance->message = $message;
        return $instance;
    }

    public function withQueueName(string $queueName): self
    {
        $instance = clone $this;
        $instance->queueName = $queueName;
        return $instance;
    }
}
