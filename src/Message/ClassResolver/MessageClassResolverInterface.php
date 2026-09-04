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
     * @param string $type Message type. Must be a non-empty string.
     *
     * @return string|null Message class, or `null` if the type is not registered.
     *
     * @psalm-param non-empty-string $type
     * @psalm-return class-string<MessageInterface>|null
     */
    public function resolve(string $type): ?string;
}
