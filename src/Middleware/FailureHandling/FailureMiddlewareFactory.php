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
 * @template-extends MiddlewareFactory<FailureMiddlewareInterface>
 */
final class FailureMiddlewareFactory extends MiddlewareFactory implements FailureMiddlewareFactoryInterface
{
    /**
     * @param array|callable|FailureMiddlewareInterface|string $middlewareDefinition Middleware definition in one of
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
     * @return FailureMiddlewareInterface
     */
    public function createFailureMiddleware(
        FailureMiddlewareInterface|callable|array|string $middlewareDefinition,
    ): FailureMiddlewareInterface {
        if ($middlewareDefinition instanceof FailureMiddlewareInterface) {
            return $middlewareDefinition;
        }

        $middleware = $this->create($middlewareDefinition);

        if (!$middleware instanceof FailureMiddlewareInterface) {
            // self::create() always returns an instance of the required interface or throws,
            // so this is unreachable at runtime and kept only for the static analyzer.
            // @codeCoverageIgnoreStart
            throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
            // @codeCoverageIgnoreEnd
        }

        return $middleware;
    }

    protected function getInterfaceName(): string
    {
        return FailureMiddlewareInterface::class;
    }

    protected function wrapMiddleware(callable $callback): FailureMiddlewareInterface
    {
        $container = $this->container;
        return new class ($callback, $container) implements FailureMiddlewareInterface {
            private $callback;

            public function __construct(
                callable $callback,
                private readonly ContainerInterface $container,
            ) {
                $this->callback = $callback;
            }

            public function processFailure(FailureHandlingRequest $request, FailureHandlerInterface $handler): FailureHandlingRequest
            {
                $response = (new Injector($this->container))->invoke($this->callback, [$request, $handler]);
                if ($response instanceof FailureHandlingRequest) {
                    return $response;
                }

                if ($response instanceof FailureMiddlewareInterface) {
                    return $response->processFailure($request, $handler);
                }

                throw new InvalidMiddlewareDefinitionException($this->callback);
            }
        };
    }
}
