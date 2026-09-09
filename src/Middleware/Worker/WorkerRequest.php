<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

use BackedEnum;
use Closure;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\StringNormalizer;

final class WorkerRequest
{
    private readonly string $queueName;

    /** @param Closure(MessageInterface): MessageInterface $retry */
    public function __construct(
        private readonly MessageInterface $message,
        string|BackedEnum $queueName,
        private readonly ?Closure $retry = null,
    ) {
        $this->queueName = StringNormalizer::normalize($queueName);
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
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
        return new self($message, $this->queueName, $this->retry);
    }
}
