<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * Envelope is a message container that adds additional metadata.
 */
interface EnvelopeInterface extends MessageInterface
{
    /** @psalm-suppress MissingClassConstType */
    public const ENVELOPE_STACK_KEY = 'envelopes';

    public static function fromMessage(MessageInterface $message): self;

    public function getMessage(): MessageInterface;

    public function withMessage(MessageInterface $message): self;

    /**
     * Finds an envelope in the current envelope stack or creates a new one from the message.
     *
     * @template T
     *
     * @psalm-param T<class-string<EnvelopeInterface>> $className
     * @throws NotEnvelopInterfaceException Implementation MUST throw this exception if the given class does not
     *         implement {@see EnvelopeInterface}.
     *
     * @psalm-return T
     */
    public function getEnvelopeFromStack(string $className): EnvelopeInterface;
}
