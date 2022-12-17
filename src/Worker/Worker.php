<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Worker;

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
use Yiisoft\Yii\Queue\Exception\JobFailureException;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeFinalHandler;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureFinalHandler;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Yii\Queue\QueueInterface;

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
     * @param MessageInterface $message
     * @param QueueInterface $queue
     *
     * @throws Throwable
     *
     * @return MessageInterface
     */
    public function process(MessageInterface $message, QueueInterface $queue): MessageInterface
    {
        $this->logger->info('Processing message #{message}.', ['message' => $message->getId()]);

        $name = $message->getHandlerName();
        $handler = $this->getHandler($name);
        if ($handler === null) {
            throw new RuntimeException("Queue handler with name $name doesn't exist");
        }

        $request = new ConsumeRequest($message, $queue);
        try {
            return $this->consumeMiddlewareDispatcher->dispatch($request, $this->createConsumeHandler($handler[0]::class))->getMessage();
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
     *
     * @return callable|null
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

    private function createConsumeHandler(string $handler): MessageHandlerConsumeInterface
    {
        return new ConsumeFinalHandler($handler, $this->logger);
    }

    private function createFailureHandler(): MessageFailureHandlerInterface
    {
        return new FailureFinalHandler();
    }
}
