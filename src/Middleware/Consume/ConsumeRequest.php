<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

final class ConsumeRequest
{
    public function __construct(private MessageInterface $message, private QueueInterface $queue)
    {
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function getQueue(): QueueInterface
    {
        return $this->queue;
    }

    public function withMessage(MessageInterface $message): self
    {
        $instance = clone $this;
        $instance->message = $message;

        return $instance;
    }

    public function withQueue(QueueInterface $queue): self
    {
        $instance = clone $this;
        $instance->queue = $queue;

        return $instance;
    }
}
