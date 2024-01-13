<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\EnvelopeTrait;
use Yiisoft\Queue\Message\MessageInterface;

final class FailureEnvelope implements EnvelopeInterface
{
    use EnvelopeTrait;

    public function __construct(
        private MessageInterface $message,
        private array $meta,
    ) {
    }

    public function getMetadata(): array
    {
        return array_merge($this->message->getMetadata(), $this->meta);
    }

}
