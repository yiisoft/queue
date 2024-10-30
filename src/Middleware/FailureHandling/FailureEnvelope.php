<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\EnvelopeTrait;
use Yiisoft\Queue\Message\MessageInterface;

final class FailureEnvelope implements EnvelopeInterface
{
    use EnvelopeTrait;

    public const FAILURE_META_KEY = 'failure-meta';

    public function __construct(
        private MessageInterface $message,
        private array $meta = [],
    ) {
    }

    public static function fromMessage(MessageInterface $message): self
    {
        return new self($message, $message->getMetadata()[self::FAILURE_META_KEY] ?? []);
    }

    public function getMetadata(): array
    {
        $meta = $this->message->getMetadata();
        $meta[self::FAILURE_META_KEY] = array_merge($meta[self::FAILURE_META_KEY] ?? [], $this->meta);

        return $meta;
    }
}
