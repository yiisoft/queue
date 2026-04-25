<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Middleware\MiddlewareFactory;

/**
 * Creates a middleware based on the definition provided.
 *
 * @template-extends MiddlewareFactory<MiddlewareFailureInterface>
 */
final class MiddlewareFactoryFailure extends MiddlewareFactory implements MiddlewareFactoryFailureInterface
{
    /**
     * @param array|callable|MiddlewareFailureInterface|string $middlewareDefinition Middleware definition in one of
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
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidMiddlewareDefinitionException
     *
     * @return MiddlewareFailureInterface
     */
    public function createFailureMiddleware(
        MiddlewareFailureInterface|callable|array|string $middlewareDefinition,
    ): MiddlewareFailureInterface {
        if ($middlewareDefinition instanceof MiddlewareFailureInterface) {
            return $middlewareDefinition;
        }

        $middleware = $this->create($middlewareDefinition);

        if (!$middleware instanceof MiddlewareFailureInterface) {
            throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
        }

        return $middleware;
    }

    protected function getInterfaceName(): string
    {
        return MiddlewareFailureInterface::class;
    }

    protected function wrapMiddleware(callable $callback): MiddlewareFailureInterface
    {
        $container = $this->container;
        return new class ($callback, $container) implements MiddlewareFailureInterface {
            private $callback;

            public function __construct(
                callable $callback,
                private readonly ContainerInterface $container,
            ) {
                $this->callback = $callback;
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
}
