<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\FailureHandling;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\InvalidCallableConfigurationException;
use Yiisoft\Yii\Queue\Middleware\InvalidMiddlewareDefinitionException;

use function is_string;

/**
 * Creates a middleware based on the definition provided.
 */
final class MiddlewareFactoryFailure implements MiddlewareFactoryFailureInterface
{
    /**
     * @param ContainerInterface $container Container to use for resolving definitions.
     */
    public function __construct(
        private ContainerInterface $container,
        private CallableFactory $callableFactory,
    ) {
    }

    /**
     * @param array|callable|MiddlewareFailureInterface|string $middlewareDefinition Middleware definition in one of the
     *     following formats:
     *
     * - A middleware object.
     * - A name of a middleware class. The middleware instance will be obtained from container and executed.
     * - A callable with `function(ServerRequestInterface $request, RequestHandlerInterface $handler):
     *     ResponseInterface` signature.
     * - A controller handler action in format `[TestController::class, 'index']`. `TestController` instance will
     *   be created and `index()` method will be executed.
     * - A function returning a middleware. The middleware returned will be executed.
     *
     * For handler action and callable
     * typed parameters are automatically injected using dependency injection container.
     * Current request and handler could be obtained by type-hinting for {@see ServerRequestInterface}
     * and {@see RequestHandlerInterface}.
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     *
     * @return MiddlewareFailureInterface
     */
    public function createFailureMiddleware(
        MiddlewareFailureInterface|callable|array|string $middlewareDefinition
    ): MiddlewareFailureInterface {
        if ($middlewareDefinition instanceof MiddlewareFailureInterface) {
            return $middlewareDefinition;
        }

        if (is_string($middlewareDefinition)) {
            return $this->getFromContainer($middlewareDefinition);
        }

        return $this->tryGetFromCallable($middlewareDefinition)
            ?? $this->tryGetFromArrayDefinition($middlewareDefinition)
            ?? throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
    }

    private function getFromContainer(string $middlewareDefinition): MiddlewareFailureInterface
    {
        if (class_exists($middlewareDefinition)) {
            if (is_subclass_of($middlewareDefinition, MiddlewareFailureInterface::class)) {
                /** @var MiddlewareFailureInterface */
                return $this->container->get($middlewareDefinition);
            }
        } elseif ($this->container->has($middlewareDefinition)) {
            $middleware = $this->container->get($middlewareDefinition);
            if ($middleware instanceof MiddlewareFailureInterface) {
                return $middleware;
            }
        }

        throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
    }

    private function wrapCallable(callable $callback): MiddlewareFailureInterface
    {
        return new class ($callback, $this->container) implements MiddlewareFailureInterface {
            private ContainerInterface $container;
            private $callback;

            public function __construct(callable $callback, ContainerInterface $container)
            {
                $this->callback = $callback;
                $this->container = $container;
            }

            public function processFailure(FailureHandlingRequest $request, MessageFailureHandlerInterface $handler): FailureHandlingRequest
            {
                $response = (new Injector($this->container))->invoke($this->callback, [$request, $handler]);
                if ($response instanceof FailureHandlingRequest) {
                    return $response;
                }

                if ($response instanceof MiddlewareFailureInterface) {
                    return $response->processFailure($request, $handler);
                }

                throw new InvalidMiddlewareDefinitionException($this->callback);
            }
        };
    }

    private function tryGetFromCallable(callable|MiddlewareFailureInterface|array|string $definition
    ): ?MiddlewareFailureInterface {
        if ($definition instanceof Closure) {
            return $this->wrapCallable($definition);
        }

        if (
            is_array($definition)
            && array_keys($definition) === [0, 1]
        ) {
            try {
                return $this->wrapCallable($this->callableFactory->create($definition));
            } catch (InvalidCallableConfigurationException $exception) {
                throw new InvalidMiddlewareDefinitionException($definition, previous: $exception);
            }
        } else {
            return null;
        }
    }

    private function tryGetFromArrayDefinition(callable|MiddlewareFailureInterface|array|string $definition
    ): ?MiddlewareFailureInterface {
        if (!is_array($definition)) {
            return null;
        }

        try {
            DefinitionValidator::validateArrayDefinition($definition);

            $middleware = ArrayDefinition::fromConfig($definition)->resolve($this->container);
            if ($middleware instanceof MiddlewareFailureInterface) {
                return $middleware;
            }

            throw new InvalidMiddlewareDefinitionException($definition);
        } catch (InvalidConfigException) {
        }

        throw new InvalidMiddlewareDefinitionException($definition);
    }
}
