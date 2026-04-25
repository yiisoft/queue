<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

use Psr\Container\ContainerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Middleware\MiddlewareFactory;

/**
 * Creates a middleware based on the definition provided.
 *
 * @template-extends MiddlewareFactory<MiddlewareConsumeInterface>
 */
final class MiddlewareFactoryConsume extends MiddlewareFactory implements MiddlewareFactoryConsumeInterface
{
    /**
     * @param MiddlewareConsumeInterface|callable|array|string $middlewareDefinition Middleware definition in one of
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
        MiddlewareConsumeInterface|callable|array|string $middlewareDefinition,
    ): MiddlewareConsumeInterface {
        if ($middlewareDefinition instanceof MiddlewareConsumeInterface) {
            return $middlewareDefinition;
        }

        $middleware = $this->create($middlewareDefinition);

        if (!$middleware instanceof MiddlewareConsumeInterface) {
            throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
        }

        return $middleware;
    }

    protected function getInterfaceName(): string
    {
        return MiddlewareConsumeInterface::class;
    }

    protected function wrapMiddleware(callable $callback): MiddlewareConsumeInterface
    {
        $container = $this->container;
        return new class ($callback, $container) implements MiddlewareConsumeInterface {
            private $callback;

            public function __construct(
                callable $callback,
                private readonly ContainerInterface $container,
            ) {
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
}
