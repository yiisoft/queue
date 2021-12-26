<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Worker;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Throwable;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Queue\Event\AfterExecution;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Exception\JobFailureException;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\QueueInterface;

final class Worker implements WorkerInterface
{
    private array $handlersCached = [];
    private EventDispatcherInterface $dispatcher;
    private LoggerInterface $logger;
    private array $handlers;
    private Injector $injector;
    private ContainerInterface $container;

    public function __construct(
        array $handlers,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        Injector $injector,
        ContainerInterface $container
    ) {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->handlers = $handlers;
        $this->injector = $injector;
        $this->container = $container;
    }

    /**
     * @param MessageInterface $message
     * @param QueueInterface $queue
     *
     * @throws Throwable
     */
    public function process(MessageInterface $message, QueueInterface $queue): void
    {
        $this->logger->debug('Start working with message #{message}.', ['message' => $message->getId()]);

        $name = $message->getName();
        $handler = $this->getHandler($name);
        if ($handler === null) {
            throw new RuntimeException("No handler for message $name");
        }

        try {
            $event = new BeforeExecution($queue, $message);
            $this->dispatcher->dispatch($event);

            if ($event->isExecutionStopped() === false) {
                $this->injector->invoke($handler, [$message]);

                $event = new AfterExecution($queue, $message);
                $this->dispatcher->dispatch($event);
            } else {
                $this->logger->notice(
                    'Execution of message #{message} is stopped by an event handler.',
                    ['message' => $message->getId()]
                );
            }
        } catch (Throwable $exception) {
            $exception = new JobFailureException($message, $exception);
            $this->logger->error($exception->getMessage());

            $event = new JobFailure($queue, $message, $exception);
            $this->dispatcher->dispatch($event);

            if ($event->shouldThrowException() === true) {
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
     * @param array|callable|object|null $definition
     *
     * @return callable|null
     */
    private function prepare($definition)
    {
        if (is_string($definition) && $this->container->has($definition)) {
            return $this->container->get($definition);
        }

        if (is_array($definition)
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
                return null;
            }

            try {
                $reflection = new ReflectionMethod($className, $methodName);
            } catch (ReflectionException $e) {
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
}
