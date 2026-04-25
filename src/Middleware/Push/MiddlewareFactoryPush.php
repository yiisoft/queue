<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Psr\Container\ContainerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Middleware\MiddlewareFactory;

/**
 * Creates a middleware based on the definition provided.
 *
 * @template-extends MiddlewareFactory<MiddlewarePushInterface>
 */
final class MiddlewareFactoryPush extends MiddlewareFactory implements MiddlewareFactoryPushInterface
{
    /**
     * @param MiddlewarePushInterface|callable|array|string $middlewareDefinition Middleware definition in one of
     * the following formats:
     *
     * - A middleware object.
     * - A name of a middleware class. The middleware instance will be obtained from container and executed.
     * - A callable with `function(MessageInterface $message, MessageHandlerPushInterface $handler):
     *     MessageInterface` signature.
     * - A controller handler action in format `[TestController::class, 'index']`. `TestController` instance will
     *   be created and `index()` method will be executed.
     * - A function returning a middleware. The middleware returned will be executed.
     *
     * For handler action and callable
     * typed parameters are automatically injected using dependency injection container.
     * Current message and handler could be obtained by type-hinting for {@see MessageInterface}
     * and {@see MessageHandlerPushInterface}.
     *
     * @throws InvalidMiddlewareDefinitionException
     *
     * @return MiddlewarePushInterface
     */
    public function createPushMiddleware(
        MiddlewarePushInterface|callable|array|string $middlewareDefinition,
    ): MiddlewarePushInterface {
        if ($middlewareDefinition instanceof MiddlewarePushInterface) {
            return $middlewareDefinition;
        }

        $middleware = $this->create($middlewareDefinition);

        if (!$middleware instanceof MiddlewarePushInterface) {
            throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
        }

        return $middleware;
    }

    protected function getInterfaceName(): string
    {
        return MiddlewarePushInterface::class;
    }

    protected function wrapMiddleware(callable $callback): MiddlewarePushInterface
    {
        return new class ($callback, $this->container) implements MiddlewarePushInterface {
            private $callback;

            public function __construct(
                callable $callback,
                private readonly ContainerInterface $container,
            ) {
                $this->callback = $callback;
            }

            public function processPush(MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface
            {
                $response = (new Injector($this->container))->invoke($this->callback, [$message, $handler]);
                if ($response instanceof MessageInterface) {
                    return $response;
                }

                if ($response instanceof MiddlewarePushInterface) {
                    return $response->processPush($message, $handler);
                }

                throw new InvalidMiddlewareDefinitionException($this->callback);
            }
        };
    }
}
