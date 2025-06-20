<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

use Closure;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\InvalidCallableConfigurationException;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;

use function is_string;

/**
 * Creates a middleware based on the definition provided.
 */
final class MiddlewareFactoryConsume implements MiddlewareFactoryConsumeInterface
{
    /**
     * @param ContainerInterface $container Container to use for resolving definitions.
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly CallableFactory $callableFactory,
    ) {
    }

    /**
     * @param array|callable|MiddlewareConsumeInterface|string $middlewareDefinition Middleware definition in one of
     * the following formats:
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
     * @throws InvalidMiddlewareDefinitionException
     *
     * @return MiddlewareConsumeInterface
     */
    public function createConsumeMiddleware(
        MiddlewareConsumeInterface|callable|array|string $middlewareDefinition
    ): MiddlewareConsumeInterface {
        if ($middlewareDefinition instanceof MiddlewareConsumeInterface) {
            return $middlewareDefinition;
        }

        if (is_string($middlewareDefinition)) {
            return $this->getFromContainer($middlewareDefinition);
        }

        return $this->tryGetFromCallable($middlewareDefinition)
            ?? $this->tryGetFromArrayDefinition($middlewareDefinition)
            ?? throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
    }

    private function getFromContainer(string $middlewareDefinition): MiddlewareConsumeInterface
    {
        if (class_exists($middlewareDefinition)) {
            if (is_subclass_of($middlewareDefinition, MiddlewareConsumeInterface::class)) {
                /** @var MiddlewareConsumeInterface */
                return $this->container->get($middlewareDefinition);
            }
        } elseif ($this->container->has($middlewareDefinition)) {
            $middleware = $this->container->get($middlewareDefinition);
            if ($middleware instanceof MiddlewareConsumeInterface) {
                return $middleware;
            }
        }

        throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
    }

    private function wrapCallable(callable $callback): MiddlewareConsumeInterface
    {
        return new class ($callback, $this->container) implements MiddlewareConsumeInterface {
            private $callback;

            public function __construct(
                callable $callback,
                private readonly ContainerInterface $container
            )
            {
                $this->callback = $callback;
            }

            public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest
            {
                $response = (new Injector($this->container))->invoke($this->callback, [$request, $handler]);
                if ($response instanceof ConsumeRequest) {
                    return $response;
                }

                if ($response instanceof MiddlewareConsumeInterface) {
                    return $response->processConsume($request, $handler);
                }

                throw new InvalidMiddlewareDefinitionException($this->callback);
            }
        };
    }

    private function tryGetFromCallable(
        callable|MiddlewareConsumeInterface|array|string $definition
    ): ?MiddlewareConsumeInterface {
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

    private function tryGetFromArrayDefinition(
        callable|MiddlewareConsumeInterface|array|string $definition
    ): ?MiddlewareConsumeInterface {
        if (!is_array($definition)) {
            return null;
        }

        try {
            DefinitionValidator::validateArrayDefinition($definition);

            $middleware = ArrayDefinition::fromConfig($definition)->resolve($this->container);
            if ($middleware instanceof MiddlewareConsumeInterface) {
                return $middleware;
            }

            throw new InvalidMiddlewareDefinitionException($definition);
        } catch (InvalidConfigException) {
        }

        throw new InvalidMiddlewareDefinitionException($definition);
    }
}
