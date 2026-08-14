<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Handler\Resolver;

use Yiisoft\Queue\Message\Handler\HandlerInterface;

/**
 * Resolves a message type to a handler.
 */
interface HandlerResolverInterface
{
    /**
     * Get a handler for the given message type.
     *
     * @param string $messageType Message type.
     *
     * @throws HandlerNotFoundException If no handler exists for the message type.
     */
    public function resolve(string $messageType): HandlerInterface;
}
