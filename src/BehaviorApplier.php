<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Exception\BehaviorMisconfiguredException;
use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;
use Yiisoft\Yii\Queue\Message\Behaviors\BehaviorInterface;

final class BehaviorApplier
{
    private array $behaviorMap;
    private ContainerInterface $container;
    private Injector $injector;
    private array $behaviorMapCached = [];

    public function __construct(array $behaviorMap, ContainerInterface $container, Injector $injector)
    {
        $this->behaviorMap = $behaviorMap;
        $this->container = $container;
        $this->injector = $injector;
    }

    public function apply(BehaviorInterface $behavior, AdapterInterface $adapter): AdapterInterface
    {
        $behaviorClass = get_class($behavior);
        $adapterClass = get_class($adapter);
        if (!isset($this->behaviorMapCached[$adapterClass][$behaviorClass])) {
            $this->behaviorMapCached[$adapterClass][$behaviorClass] = $this->findApplier($behavior, $adapter);
        }

        return $this->behaviorMapCached[$adapterClass][$behaviorClass]($behavior, $adapter);
    }

    private function findApplier(BehaviorInterface $behavior, AdapterInterface $adapter): callable
    {
        $definition = $this->getDefinition($behavior, $this->behaviorMap[get_class($adapter)] ?? []);
        if ($definition === null) {
            return [$this, 'throwNotSupported'];
        }

        $callable = $this->prepareCallable($definition);
        if ($callable === null) {
            return [$this, 'throwMisconfigured'];
        }

        return function (BehaviorInterface $behavior, AdapterInterface $adapter) use ($callable): AdapterInterface {
            return $this->injector->invoke($callable, [$behavior, $adapter]);
        };
    }

    private function throwNotSupported(BehaviorInterface $behavior, AdapterInterface $adapter): void
    {
        throw new BehaviorNotSupportedException($adapter, $behavior);
    }

    private function throwMisconfigured(BehaviorInterface $behavior, AdapterInterface $adapter): void
    {
        throw new BehaviorMisconfiguredException($adapter, $behavior);
    }

    /**
     * @return mixed
     */
    private function getDefinition(BehaviorInterface $behavior, array $map)
    {
        $behaviorClass = get_class($behavior);
        if (isset($map[$behaviorClass])) {
            return $map[$behaviorClass];
        }

        foreach (class_parents($behavior) as $className) {
            if (isset($map[$className])) {
                return $map[$className];
            }
        }

        foreach (class_implements($behavior) as $className) {
            if (isset($map[$className])) {
                return $map[$className];
            }
        }

        return null;
    }

    /**
     * Checks if the handler is a DI container alias
     *
     * @param array|callable|object|null $definition
     *
     * @return callable|null
     */
    private function prepareCallable($definition): ?callable
    {
        if (is_string($definition) && $this->container->has($definition)) {
            return $this->container->get($definition);
        }

        if (is_array($definition)
            && array_keys($definition) === [0, 1]
            && is_string($definition[0])
            && is_string($definition[1])
        ) {
            [$className, $methodName] = $definition;

            if (!class_exists($className) && $this->container->has($className)) {
                return [
                    $this->container->get($className),
                    $methodName,
                ];
            }

            if (!class_exists($className)) {
                return null;
            }

            try {
                $reflection = new ReflectionMethod($className, $methodName);
            } catch (ReflectionException $e) {
                return null;
            }
            if ($reflection->isStatic()) {
                return [$className, $methodName];
            }
            if ($this->container->has($className)) {
                return [
                    $this->container->get($className),
                    $methodName,
                ];
            }

            return null;
        }

        return $definition;
    }
}
