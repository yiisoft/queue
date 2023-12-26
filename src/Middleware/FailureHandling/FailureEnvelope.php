<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\FailureHandling;

use Yiisoft\Yii\Queue\Message\EnvelopeInterface;
use Yiisoft\Yii\Queue\Message\EnvelopeTrait;
use Yiisoft\Yii\Queue\Message\MessageInterface;

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
