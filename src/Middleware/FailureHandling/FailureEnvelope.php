<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Queue\Message\Envelope;
use Yiisoft\Queue\Message\MessageInterface;

final class FailureEnvelope extends Envelope
{
    public const META_FAILURE_METADATA = 'yii-failure-metadata';

    public function __construct(MessageInterface $message, array $failureMetadata = [])
    {
        parent::__construct($message, [
            self::META_FAILURE_METADATA => ArrayHelper::merge($message->getMetadata()[self::META_FAILURE_METADATA] ?? [], $failureMetadata),
        ]);
    }

    public static function fromMessage(MessageInterface $message): static
    {
        /** @var array $metadata */
        $metadata = $message->getMetadata()[self::META_FAILURE_METADATA] ?? [];

        return new self($message, $metadata);
    }
}
