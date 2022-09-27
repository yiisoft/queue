<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Push;

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
final class MiddlewareFactoryPush implements MiddlewareFactoryPushInterface
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
     * @param array|callable|MiddlewarePushInterface|string $middlewareDefinition Middleware definition in one of the
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
     */
    public function createPushMiddleware(
        MiddlewarePushInterface|callable|array|string $middlewareDefinition
    ): MiddlewarePushInterface {
        if ($middlewareDefinition instanceof MiddlewarePushInterface) {
            return $middlewareDefinition;
        }

        if (is_string($middlewareDefinition) && is_subclass_of($middlewareDefinition, MiddlewarePushInterface::class)) {
            /** @var MiddlewarePushInterface */
            return $this->container->get($middlewareDefinition);
        }

        $callable = $this->callableFactory->create($middlewareDefinition);

        return $this->wrapCallable($callable);
    }

    private function wrapCallable(callable $callback): MiddlewarePushInterface
    {
        return new class ($callback, $this->container) implements MiddlewarePushInterface {
            public function __construct(private callable $callback, private ContainerInterface $container)
            {
            }

            public function processPush(PushRequest $request, MessageHandlerPushInterface $handler): PushRequest
            {
                $response = (new Injector($this->container))->invoke($this->callback, [$request, $handler]);
                if ($response instanceof PushRequest) {
                    return $response;
                }

                if ($response instanceof MiddlewarePushInterface) {
                    return $response->processPush($request, $handler);
                }

                throw new InvalidMiddlewareDefinitionException($this->callback);
            }
        };
    }
}
