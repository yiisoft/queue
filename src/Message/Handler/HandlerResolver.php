<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Handler;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\InvalidCallableConfigurationException;

use function array_key_exists;
use function is_callable;
use function is_string;
use function sprintf;

/**
 * Resolves message handlers from configuration, a DI container, or a callable factory.
 */
final class HandlerResolver
{
    /**
     * @var HandlerInterface[] Cache of resolved handlers.
     * @psalm-var array<non-empty-string, HandlerInterface>
     */
    private array $cache = [];

    private readonly Injector $injector;
    private readonly CallableFactory $callableFactory;

    /**
     * @param (array|callable|HandlerInterface|string)[] $handlers Handler definitions indexed by message type.
     * @param ContainerInterface $container Container used to resolve handlers.
     * @param ContainerInterface|null $callableDependencyContainer Container used to resolve callable handler
     * dependencies. If not set, the main container is used.
     *
     * @psalm-param array<non-empty-string, array|callable|HandlerInterface|string> $handlers
     */
    public function __construct(
        private readonly array $handlers,
        private readonly ContainerInterface $container,
        ?ContainerInterface $callableDependencyContainer = null,
    ) {
        $this->injector = new Injector($callableDependencyContainer ?? $this->container);
        $this->callableFactory = new CallableFactory($this->container);
    }

    /**
     * Get a handler for the given message type.
     *
     * @param string $messageType Message type. Must be a non-empty string.
     *
     * @psalm-param non-empty-string $messageType
     *
     * @throws HandlerNotFoundException If no handler exists for the message type.
     * @throws InvalidHandlerConfigurationException If the handler definition is configured incorrectly.
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     */
    public function resolve(string $messageType): HandlerInterface
    {
        if (array_key_exists($messageType, $this->cache)) {
            return $this->cache[$messageType];
        }

        $this->cache[$messageType] = $this->internalResolve($messageType);

        return $this->cache[$messageType];
    }

    /**
     * @throws HandlerNotFoundException
     * @throws InvalidHandlerConfigurationException
     * @throws ContainerExceptionInterface
     */
    private function internalResolve(string $messageType): HandlerInterface
    {
        $definition = $this->handlers[$messageType] ?? $messageType;

        if ($definition instanceof HandlerInterface) {
            return $definition;
        }

        if (is_callable($definition)) {
            return $this->createCallableHandler($messageType, $definition);
        }

        if (is_string($definition)) {
            return $this->getHandlerFromContainer($messageType, $definition);
        }

        return $this->createCallableHandler($messageType, $definition);
    }

    /**
     * @throws HandlerNotFoundException
     * @throws InvalidHandlerConfigurationException
     * @throws ContainerExceptionInterface
     */
    private function getHandlerFromContainer(string $messageType, string $id): HandlerInterface
    {
        if (!$this->container->has($id)) {
            throw new HandlerNotFoundException($messageType);
        }

        $handler = $this->container->get($id);

        if ($handler instanceof HandlerInterface) {
            return $handler;
        }

        if (is_callable($handler)) {
            return $this->createCallableHandler($messageType, $handler);
        }

        throw new InvalidHandlerConfigurationException(
            $messageType,
            sprintf(
                'Resolved from container handler should be an instance of "%s" or callable, got "%s".',
                HandlerInterface::class,
                get_debug_type($handler),
            ),
        );
    }

    /**
     * @throws InvalidHandlerConfigurationException
     * @throws ContainerExceptionInterface
     */
    private function createCallableHandler(string $messageType, mixed $definition): CallableHandler
    {
        try {
            $callable = $this->callableFactory->create($definition);
        } catch (InvalidCallableConfigurationException $exception) {
            throw new InvalidHandlerConfigurationException($messageType, $exception->getMessage(), $exception);
        }

        $callable = function (MessageInterface $message) use ($callable): void {
            $this->injector->invoke($callable, [$message]);
        };

        return new CallableHandler($callable);
    }
}
