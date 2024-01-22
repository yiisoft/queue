<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Throwable;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Exception\JobFailureException;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeFinalHandler;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureFinalHandler;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class Worker implements WorkerInterface
{
    private array $handlersCached = [];

    public function __construct(
        private array $handlers,
        private LoggerInterface $logger,
        private Injector $injector,
        private ContainerInterface $container,
        private ConsumeMiddlewareDispatcher $consumeMiddlewareDispatcher,
        private FailureMiddlewareDispatcher $failureMiddlewareDispatcher,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function process(MessageInterface $message, QueueInterface $queue): MessageInterface
    {
        $this->logger->info('Processing message #{message}.', ['message' => $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? 'null']);

        $name = $message->getHandlerName();
        $handler = $this->getHandler($name);
        if ($handler === null) {
            throw new RuntimeException(sprintf('Queue handler with name "%s" does not exist', $name));
        }

        $request = new ConsumeRequest($message, $queue);
        $closure = fn (MessageInterface $message): mixed => $this->injector->invoke($handler, [$message]);
        try {
            return $this->consumeMiddlewareDispatcher->dispatch($request, $this->createConsumeHandler($closure))->getMessage();
        } catch (Throwable $exception) {
            $request = new FailureHandlingRequest($request->getMessage(), $exception, $request->getQueue());

            try {
                $result = $this->failureMiddlewareDispatcher->dispatch($request, $this->createFailureHandler());
                $this->logger->info($exception->getMessage());

                return $result->getMessage();
            } catch (Throwable $exception) {
                $exception = new JobFailureException($message, $exception);
                $this->logger->error($exception->getMessage());
                throw $exception;
            }
        }
    }

    private function getHandler(string $name): ?callable
    {
        if (!array_key_exists($name, $this->handlersCached)) {
            $this->handlersCached[$name] = $this->prepare($this->handlers[$name] ?? null);
        }

        return $this->handlersCached[$name];
    }

    /**
     * Checks if the handler is a DI container alias
     *
     * @param array|callable|object|string|null $definition
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function prepare(callable|object|array|string|null $definition): callable|null
    {
        if (is_string($definition) && $this->container->has($definition)) {
            return $this->container->get($definition);
        }

        if (
            is_array($definition)
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
                $this->logger->error("$className doesn't exist.");

                return null;
            }

            try {
                $reflection = new ReflectionMethod($className, $methodName);
            } catch (ReflectionException $e) {
                $this->logger->error($e->getMessage());

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

    private function createConsumeHandler(Closure $handler): MessageHandlerConsumeInterface
    {
        return new ConsumeFinalHandler($handler);
    }

    private function createFailureHandler(): MessageFailureHandlerInterface
    {
        return new FailureFinalHandler();
    }
}
