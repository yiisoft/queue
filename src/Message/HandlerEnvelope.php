<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * ID envelope allows to identify a message.
 */
final class HandlerEnvelope implements EnvelopeInterface
{
    use EnvelopeTrait;

    public const HANDLER_CLASS_KEY = 'handler_class';

    public function __construct(
        private MessageInterface $message,
        private string $handlerClass = '',
    ) {
    }

    public function setHandler(string $handlerClass): void
    {
        $this->handlerClass = $handlerClass;
    }

    public function getHandler(): string
    {
        return $this->handlerClass ?: $this->message->getMetadata()[self::HANDLER_CLASS_KEY];
    }

    public function getMetadata(): array
    {
        return array_merge($this->message->getMetadata(), [
            self::HANDLER_CLASS_KEY => $this->getHandler(),
        ]);
    }
}
