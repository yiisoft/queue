<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\EnvelopeTrait;
use Yiisoft\Queue\Message\MessageInterface;

final class FailureEnvelope implements EnvelopeInterface
{
    use EnvelopeTrait {
        getMetadata as getMetadataParent;
    }

    public const FAILURE_META_KEY = 'failure-meta';

    public function __construct(
        MessageInterface $message,
        private readonly array $meta = [],
    ) {
        $this->message = $message;
    }

    public static function fromMessage(MessageInterface $message): self
    {
        return new self($message, $message->getMetadata()[self::FAILURE_META_KEY] ?? []);
    }

    public function getMetadata(): array
    {
        $meta = $this->getMetadataParent();
        $meta[self::FAILURE_META_KEY] = array_merge($meta[self::FAILURE_META_KEY] ?? [], $this->meta);

        return $meta;
    }
}
