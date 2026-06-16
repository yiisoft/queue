<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\ClassResolver;

use Yiisoft\Queue\Message\MessageInterface;

/**
 * Resolves a message class by message type.
 */
interface MessageClassResolverInterface
{
    /**
     * Returns the message class for the given type, or `null` if the type is not registered.
     *
     * @param string $type Message type.
     *
     * @return string|null Message class, or `null` if the type is not registered.
     *
     * @psalm-return class-string<MessageInterface>|null
     */
    public function resolve(string $type): ?string;
}
