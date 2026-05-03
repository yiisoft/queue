<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

/**
 * Holds the push middleware factory and the list of common middleware definitions
 * applied to queues.
 */
final class PushMiddlewareConfig
{
    /**
     * @param PushMiddlewareFactoryInterface $middlewareFactory Factory used to instantiate middleware from definitions.
     * @param array<array|callable|PushMiddlewareInterface|string> $commonMiddlewareDefinitions Middleware definitions.
     */
    public function __construct(
        public readonly PushMiddlewareFactoryInterface $middlewareFactory,
        public readonly array $commonMiddlewareDefinitions = [],
    ) {}
}
