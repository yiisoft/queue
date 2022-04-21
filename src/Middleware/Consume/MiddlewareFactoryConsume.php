<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Consume;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\InvalidMiddlewareDefinitionException;

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
        private ContainerInterface $container,
        private CallableFactory $callableFactory,
    ) {
    }

    /**
     * @param array|callable|MiddlewareConsumeInterface|string $middlewareDefinition Middleware definition in one of the
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @return MiddlewareConsumeInterface
     */
    public function createConsumeMiddleware(
        MiddlewareConsumeInterface|callable|array|string $middlewareDefinition
    ): MiddlewareConsumeInterface {
        if ($middlewareDefinition instanceof MiddlewareConsumeInterface) {
            return $middlewareDefinition;
        }

        if (is_string($middlewareDefinition) && is_subclass_of($middlewareDefinition, MiddlewareConsumeInterface::class)) {
            /** @var MiddlewareConsumeInterface */
            return $this->container->get($middlewareDefinition);
        }

        $callable = $this->callableFactory->create($middlewareDefinition);

        return $this->wrapCallable($callable);
    }

    private function wrapCallable(callable $callback): MiddlewareConsumeInterface
    {
        return new class ($callback, $this->container) implements MiddlewareConsumeInterface {
            private ContainerInterface $container;
            private $callback;

            public function __construct(callable $callback, ContainerInterface $container)
            {
                $this->callback = $callback;
                $this->container = $container;
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
