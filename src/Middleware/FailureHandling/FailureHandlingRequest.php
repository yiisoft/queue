<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Closure;
use Throwable;
use BackedEnum;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\StringNormalizer;

final class FailureHandlingRequest
{
    private readonly string $queueName;

    /** @param Closure(MessageInterface): MessageInterface $retry */
    public function __construct(
        private readonly MessageInterface $message,
        private readonly Throwable $exception,
        string|BackedEnum $queueName,
        private readonly ?Closure $retry = null,
    ) {
        $this->queueName = StringNormalizer::normalize($queueName);
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /** @return Closure(MessageInterface): MessageInterface */
    public function getRetry(): ?Closure
    {
        return $this->retry;
    }

    public function withMessage(MessageInterface $message): self
    {
        return new self($message, $this->exception, $this->queueName, $this->retry);
    }

    public function withException(Throwable $exception): self
    {
        return new self($this->message, $exception, $this->queueName, $this->retry);
    }
}
