<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

use Yiisoft\Queue\Message\MessageInterface;

final class ConsumeRequest
{
    public function __construct(private MessageInterface $message, private string $queue) {}

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    /** Logical queue currently executing this message. */
    public function getQueue(): string
    {
        return $this->queue;
    }

    public function withMessage(MessageInterface $message): self
    {
        $instance = clone $this;
        $instance->message = $message;
        return $instance;
    }

    public function withQueue(string $queue): self
    {
        $instance = clone $this;
        $instance->queue = $queue;
        return $instance;
    }
}
