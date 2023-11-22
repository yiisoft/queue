<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\FailureHandling;

use Throwable;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\QueueInterface;

final class FailureHandlingRequest
{
    public function __construct(private MessageInterface $message, private Throwable $exception, private QueueInterface $queue)
    {
    }

    /**
     * @return MessageInterface
     */
    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function getException(): Throwable
    {
        return $this->exception;
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

    public function withException(Throwable $exception): self
    {
        $instance = clone $this;
        $instance->exception = $exception;

        return $instance;
    }

    public function withQueue(QueueInterface $queue): self
    {
        $instance = clone $this;
        $instance->queue = $queue;

        return $instance;
    }
}
