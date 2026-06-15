<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\ClassResolver;

use Yiisoft\Queue\Message\MessageInterface;

/**
 * Resolves message classes from a predefined map of type-to-class associations.
 */
final class ArrayMessageClassResolver implements MessageClassResolverInterface
{
    /**
     * @param array $map Map of message type to message class, where keys are message types and values are
     * fully-qualified class names implementing {@see MessageInterface}. For example:
     *
     * ```php
     * [
     *     'order.created' => OrderCreatedMessage::class,
     *     'send_email' => SendEmailMessage::class,
     * ]
     * ```
     *
     * @psalm-param array<string, class-string<MessageInterface>> $map
     */
    public function __construct(
        private readonly array $map = [],
    ) {}

    public function resolve(string $type): ?string
    {
        return $this->map[$type] ?? null;
    }
}
