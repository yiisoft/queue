<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\App;

use Yiisoft\Queue\Message\Envelope;
use Yiisoft\Queue\Message\MessageInterface;

final class DummyEnvelope extends Envelope
{
    public static function fromMessage(MessageInterface $message): static
    {
        return new static($message);
    }

    protected function getEnvelopeMetadata(): array
    {
        return [];
    }
}
