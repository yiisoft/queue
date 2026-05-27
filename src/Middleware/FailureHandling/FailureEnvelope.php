<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Queue\Message\Envelope;
use Yiisoft\Queue\Message\MessageInterface;

final class FailureEnvelope extends Envelope
{
    public const FAILURE_META_KEY = 'failure-meta';

    public function __construct(MessageInterface $message, array $metadata = [])
    {
        parent::__construct($message, [
            self::FAILURE_META_KEY => ArrayHelper::merge($message->getMetadata()[self::FAILURE_META_KEY] ?? [], $metadata),
        ]);
    }
}
