<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\App;

use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\EnvelopeTrait;
use Yiisoft\Queue\Message\MessageInterface;

final class DummyEnvelope implements EnvelopeInterface
{
    use EnvelopeTrait;

    public static function fromMessage(MessageInterface $message): self
    {
        $instance = new self();
        $instance->message = $message;

        return $instance;
    }
}
