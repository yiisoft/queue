<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Psr\Container\ContainerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Middleware\MiddlewareFactory;

use function is_array;
use function is_callable;
use function is_string;

/**
 * Creates a middleware based on the definition provided.
 *
 * @template-extends MiddlewareFactory<PushMiddlewareInterface>
 * @template-implements PushMiddlewareFactoryInterface<PushMiddlewareInterface|array|callable|string>
 */
final class PushMiddlewareFactory extends MiddlewareFactory implements PushMiddlewareFactoryInterface
{
    /**
     * @param mixed $middlewareDefinition Middleware definition in one of the following formats:
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
     * and {@see PushHandlerInterface}.
     *
     * @throws InvalidMiddlewareDefinitionException
     *
     * @return PushMiddlewareInterface
     */
    public function createPushMiddleware(mixed $middlewareDefinition): PushMiddlewareInterface
    {
        if ($middlewareDefinition instanceof PushMiddlewareInterface) {
            return $middlewareDefinition;
        }

        if (!is_callable($middlewareDefinition) && !is_array($middlewareDefinition) && !is_string($middlewareDefinition)) {
            throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
        }

        $middleware = $this->create($middlewareDefinition);

        if (!$middleware instanceof PushMiddlewareInterface) {
            throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
        }

        return $middleware;
    }

    protected function getInterfaceName(): string
    {
        return PushMiddlewareInterface::class;
    }

    protected function wrapMiddleware(callable $callback): PushMiddlewareInterface
    {
        return new class ($callback, $this->container) implements PushMiddlewareInterface {
            private $callback;

            public function __construct(
                callable $callback,
                private readonly ContainerInterface $container,
            ) {
                $this->callback = $callback;
            }

            public function processPush(MessageInterface $message, PushHandlerInterface $handler): MessageInterface
            {
                $response = (new Injector($this->container))->invoke($this->callback, [$message, $handler]);
                if ($response instanceof MessageInterface) {
                    return $response;
                }

                if ($response instanceof PushMiddlewareInterface) {
                    return $response->processPush($message, $handler);
                }

                throw new InvalidMiddlewareDefinitionException($this->callback);
            }
        };
    }
}
