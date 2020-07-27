<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Worker;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Queue\Event\AfterExecution;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Queue;

final class Worker implements WorkerInterface
{
    private array $handlersCached;
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

    public function registerHandlers(array $handlers): void
    {
        foreach ($handlers as $event => $handler) {
            $this->handlers[$event] = $handler;
        }
    }

    /**
     * @param MessageInterface $message
     * @param Queue $queue
     *
     * @throws Throwable
     */
    public function process(MessageInterface $message, Queue $queue): void
    {
        $this->logger->debug('Start working with message #{message}.', ['message' => $message->getId()]);

        $name = $message->getPayloadName();
        $handler = $this->getHandler($name);
        if ($handler === null) {
            throw new InvalidArgumentException("No handler for message $name");
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
            $this->logger->error(
                "Processing of message #{message} is stopped because of an exception:\n{exception}.",
                [
                    'message' => $message->getId(),
                    'exception' => $exception->getMessage(),
                ]
            );
            $event = new JobFailure($queue, $message, $exception);
            $this->dispatcher->dispatch($event);

            if ($event->shouldThrowException() === true) {
                throw $exception;
            }
        }
    }

    private function getHandler(string $name): ?callable
    {
        if (isset($this->handlersCached[$name])) {
            return $this->handlersCached[$name];
        }

        $this->handlersCached[$name] = null;

        $handler = $this->handlers[$name] ?? null;

        if (is_callable($handler)) {
            $this->handlersCached[$name] = $handler;
        }

        if (
            is_array($handler)
            && array_keys($handler) === [0, 1]
            && is_string($handler[0])
        ) {
            $handler[0] = $this->container->get($handler[0]);
            $this->handlersCached[$name] = $handler;
        }

        return $this->handlersCached[$name];
    }
}
