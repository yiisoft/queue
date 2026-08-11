<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Throwable;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueProducerInterface;

final class FailureHandlingRequest
{
    public function __construct(
        private MessageInterface $message,
        private Throwable $exception,
        private string $queue,
        private ?QueueProducerInterface $retryProducer = null,
    ) {}

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }

    /** Logical queue which executed the message. */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /** Direct retry target used by synchronous producer execution, if any. */
    public function getRetryProducer(): ?QueueProducerInterface
    {
        return $this->retryProducer;
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

    public function withQueue(string $queue): self
    {
        $instance = clone $this;
        $instance->queue = $queue;
        return $instance;
    }
}
