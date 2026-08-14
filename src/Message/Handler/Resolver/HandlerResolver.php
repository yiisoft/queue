<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Handler\Resolver;

use Psr\Container\ContainerInterface;
use Yiisoft\Queue\Message\Handler\CallableHandler;
use Yiisoft\Queue\Message\Handler\HandlerInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\InvalidCallableConfigurationException;

use function array_key_exists;
use function is_string;

/**
 * Resolves message handlers from configuration, a DI container, or a callable factory.
 */
final class HandlerResolver implements HandlerResolverInterface
{
    /** @var array<non-empty-string, HandlerInterface> Cache of resolved handlers */
    private array $cache = [];

    public function __construct(
        /** @var array<non-empty-string, array|callable|object|string|null> */
        private readonly array $handlers,
        private readonly ContainerInterface $container,
        private readonly CallableFactory $callableFactory,
    ) {}

    public function resolve(string $messageType): HandlerInterface
    {
        if ($messageType === '') {
            throw new HandlerNotFoundException($messageType);
        }

        if (array_key_exists($messageType, $this->cache)) {
            return $this->cache[$messageType];
        }

        $definition = $this->handlers[$messageType] ?? $messageType;

        if (is_string($definition) && $this->container->has($definition)) {
            $resolved = $this->container->get($definition);

            if ($resolved instanceof HandlerInterface) {
                return $this->cache[$messageType] = $resolved;
            }
        }

        try {
            /** @psalm-var callable(MessageInterface): void $callable */
            $callable = $this->callableFactory->create($definition);

            return $this->cache[$messageType] = new CallableHandler($callable);
        } catch (InvalidCallableConfigurationException $exception) {
            throw new HandlerNotFoundException($messageType, 0, $exception);
        }
    }
}
