<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * Envelope is a message container that adds additional metadata.
 */
interface EnvelopeInterface extends MessageInterface
{
    public const ENVELOPE_STACK_KEY = 'envelopes';

    public static function fromMessage(MessageInterface $message): self;

    public function getMessage(): MessageInterface;

    public function withMessage(MessageInterface $message): self;
}
