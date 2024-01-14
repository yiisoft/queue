<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

final class FailureEnvelope implements EnvelopeInterface
{
    use EnvelopeTrait;

    public function __construct(
        private MessageInterface $message,
        private array $meta = [],
    ) {
    }

    public function getMetadata(): array
    {
        return array_merge($this->message->getMetadata(), $this->meta);
    }
}
