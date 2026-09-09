<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use BackedEnum;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\StringNormalizer;

final class PushRequest
{
    private readonly string $queueName;

    public function __construct(
        private readonly MessageInterface $message,
        string|BackedEnum $queueName,
    ) {
        $this->queueName = StringNormalizer::normalize($queueName);
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function withMessage(MessageInterface $message): self
    {
        return new self($message, $this->queueName);
    }
}
