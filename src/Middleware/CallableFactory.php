<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;

use function is_array;
use function is_callable;
use function is_object;
use function is_string;

/**
 * @internal Create real callable listener from configuration.
 */
final class CallableFactory
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    /**
     * Create a real callable listener from definition.
     *
     * @param mixed $definition Definition to create listener from.
     *
     * @throws InvalidCallableConfigurationException Failed to create listener.
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     */
    public function create(mixed $definition): callable
    {
        if ($definition === null) {
            throw new InvalidCallableConfigurationException();
        }

        if ($definition instanceof Closure) {
            return $definition;
        }

        if (is_string($definition) && $this->container->has($definition)) {
            $result = $this->container->get($definition);

            if (is_callable($result)) {
                return $result;
            }

            throw new InvalidCallableConfigurationException();
        }

        if (is_array($definition)
            && array_keys($definition) === [0, 1]
            && is_string($definition[1])
        ) {
            if (is_object($definition[0])) {
                $callable = $this->fromObjectDefinition($definition[0], $definition[1]);
                if ($callable !== null) {
                    return $callable;
                }
            }

            if (is_string($definition[0])) {
                $callable = $this->fromDefinition($definition[0], $definition[1]);
                if ($callable !== null) {
                    return $callable;
                }
            }
        }

        if (is_callable($definition)) {
            return $definition;
        }

        throw new InvalidCallableConfigurationException();
    }

    /**
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     * @throws NotFoundExceptionInterface
     */
    private function fromDefinition(string $className, string $methodName): ?callable
    {
        $result = null;

        if (class_exists($className)) {
            try {
                $reflection = new ReflectionMethod($className, $methodName);
                if ($reflection->isStatic()) {
                    $result = [$className, $methodName];
                }
            } catch (ReflectionException) {
            }
        }

        if ($result === null && $this->container->has($className)) {
            $result = [
                $this->container->get($className),
                $methodName,
            ];
        }

        return is_callable($result) ? $result : null;
    }

    private function fromObjectDefinition(object $object, string $methodName): ?callable
    {
        try {
            new ReflectionMethod($object, $methodName);
        } catch (ReflectionException) {
            return null;
        }

        $result = [$object, $methodName];

        return is_callable($result) ? $result : null;
    }
}
