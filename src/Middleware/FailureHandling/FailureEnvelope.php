<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\AbstractEnvelope;
use Yiisoft\Queue\Message\MessageInterface;

final class FailureEnvelope extends AbstractEnvelope
{
    public const FAILURE_META_KEY = 'failure-meta';

    public function __construct(
        MessageInterface $message,
        private readonly array $failureMeta = [],
    ) {
        parent::__construct($message);
    }

    public static function fromMessage(MessageInterface $message): self
    {
        return new self($message, $message->getMetadata()[self::FAILURE_META_KEY] ?? []);
    }

    protected function getEnvelopeMetadata(): array
    {
        return [self::FAILURE_META_KEY => ArrayHelper::merge($this->metadata[self::FAILURE_META_KEY], $this->failureMeta)];
    }
}
