<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Queue\Message\Envelope;
use Yiisoft\Queue\Message\MessageInterface;

final class FailureEnvelope extends Envelope
{
    public const FAILURE_META_KEY = 'failure-meta';

    public function __construct(
        protected MessageInterface $message,
        private readonly array $metadata = [],
    ) {}

    public static function fromMessage(MessageInterface $message): static
    {
        /** @var array $metadata */
        $metadata = $message->getMetadata()[self::FAILURE_META_KEY] ?? [];

        return new self($message, $metadata);
    }

    protected function getEnvelopeMetadata(): array
    {
        /** @var array $metadata */
        $metadata = $this->message->getMetadata()[self::FAILURE_META_KEY] ?? [];

        return [self::FAILURE_META_KEY => ArrayHelper::merge($metadata, $this->metadata)];
    }
}
