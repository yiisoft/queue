<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Throwable;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Queue\QueueInterface;

final class FailureHandlingRequest extends Request
{
    public function __construct(private MessageInterface $message, private ?Throwable $exception)
    {
        parent::__construct($message, null);
    }

    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    public function getQueue(): QueueInterface
    {
        return $this->queue;
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
