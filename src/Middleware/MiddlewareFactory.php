<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;

use function is_array;
use function is_string;

/**
 * @template T of object
 * @internal
 */
abstract class MiddlewareFactory
{
    public function __construct(
        protected readonly ContainerInterface $container,
        private readonly CallableFactory $callableFactory,
    ) {}

    /**
     * @param callable|array|string $definition Middleware definition in one of the following formats:
     *
     * @return T Middleware instance
     * @throws InvalidMiddlewareDefinitionException
     */
    protected function create(callable|array|string $definition): object
    {
        try {
            $callable = $this->callableFactory->create($definition);

            return $this->wrapMiddleware($callable);
        } catch (InvalidCallableConfigurationException) {
            // Not a callable, try internal checks
        }

        if (is_string($definition)) {
            return $this->getFromContainer($definition);
        }

        if (is_array($definition)) {
            return $this->tryGetFromArrayDefinition($definition)
                ?? throw new InvalidMiddlewareDefinitionException($definition);
        }

        throw new InvalidMiddlewareDefinitionException($definition);
    }

    /**
     * @return class-string<T> Required interface FQCN
     */
    abstract protected function getInterfaceName(): string;

    /**
     * @param callable $callback Callable to wrap
     * @return T Wrapped middleware
     */
    abstract protected function wrapMiddleware(callable $callback): object;

    /**
     * @return T
     * @throws InvalidMiddlewareDefinitionException
     */
    private function getFromContainer(string $definition): object
    {
        $interface = $this->getInterfaceName();

        if ($this->container->has($definition)) {
            /** @var object $middleware */
            $middleware = $this->container->get($definition);
            if (is_subclass_of($middleware, $interface)) {
                /** @var T */
                return $middleware;
            }
        }

        throw new InvalidMiddlewareDefinitionException($definition);
    }

    /**
     * @return T|null
     * @throws InvalidMiddlewareDefinitionException
     */
    private function tryGetFromArrayDefinition(array $definition): ?object
    {
        $interface = $this->getInterfaceName();

        try {
            DefinitionValidator::validateArrayDefinition($definition);

            $middleware = ArrayDefinition::fromConfig($definition)->resolve($this->container);
            if (is_subclass_of($middleware, $interface)) {
                /** @var T */
                return $middleware;
            }

            throw new InvalidMiddlewareDefinitionException($definition);
        } catch (InvalidConfigException) {
        }

        throw new InvalidMiddlewareDefinitionException($definition);
    }
}
