<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Handler;

use Yiisoft\Queue\Message\MessageInterface;

/**
 * Handles a message.
 */
interface HandlerInterface
{
    /**
     * Handle the given message.
     */
    public function handle(MessageInterface $message): void;
}
