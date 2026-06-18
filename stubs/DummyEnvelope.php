<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use Yiisoft\Queue\Message\Envelope;
use Yiisoft\Queue\Message\MessageInterface;

/**
 * Dummy envelope stub for testing purposes.
 *
 * @extends Envelope<array<never,never>>
 */
final class DummyEnvelope extends Envelope
{
    public function __construct(MessageInterface $message)
    {
        parent::__construct($message, []);
    }

    public static function fromMessage(MessageInterface $message): static
    {
        return new self($message);
    }
}
