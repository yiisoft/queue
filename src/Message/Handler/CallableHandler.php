<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Handler;

use Yiisoft\Queue\Message\MessageInterface;

/**
 * Handles a message by invoking the given callable.
 *
 * @psalm-type MessageHandlerCallable = callable(MessageInterface $message): void
 */
final class CallableHandler implements HandlerInterface
{
    /**
     * @param callable $handler Callable invoked to handle a message.
     * Format: `function (MessageInterface $message): void`.
     *
     * @psalm-param MessageHandlerCallable $handler
     */
    public function __construct(
        private readonly mixed $handler,
    ) {}

    public function handle(MessageInterface $message): void
    {
        ($this->handler)($message);
    }
}
