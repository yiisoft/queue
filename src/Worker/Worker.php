<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Exception\JobFailureException;
use Yiisoft\Queue\Message\HandlerEnvelope;
use Yiisoft\Queue\Message\MessageHandlerInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\ConsumeFinalHandler;
use Yiisoft\Queue\Middleware\FailureHandling\FailureFinalHandler;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class Worker implements WorkerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private Injector $injector,
        private ContainerInterface $container,
        private MiddlewareDispatcher $consumeMiddlewareDispatcher,
        private FailureMiddlewareDispatcher $failureMiddlewareDispatcher,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function process(MessageInterface $message, QueueInterface $queue): MessageInterface
    {
        $this->logger->info('Processing message #{message}.', ['message' => $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? 'null']);

        $handlerClass = $message instanceof HandlerEnvelope ? $message->getHandler() : null;

        if (!is_subclass_of($handlerClass, MessageHandlerInterface::class, true)) {
            throw new RuntimeException(sprintf(
                'Message handler "%s" for "%s" must implement "%s".',
                $handlerClass,
                $message::class,
                MessageHandlerInterface::class,
            ));
        }
        $handler = $this->container->get($handlerClass);
        if ($handler === null) {
            throw new RuntimeException(sprintf('Queue handler with name "%s" does not exist', $handlerClass));
        }

        $request = new Request($message, $queue->getAdapter());
        $closure = fn (MessageInterface $message): mixed => $this->injector->invoke([$handler, 'handle'], [$message]);
        try {
            return $this->consumeMiddlewareDispatcher->dispatch($request, new ConsumeFinalHandler($closure))->getMessage();
        } catch (Throwable $exception) {
            $request = new FailureHandlingRequest($request->getMessage(), $exception, $queue);

            try {
                $result = $this->failureMiddlewareDispatcher->dispatch($request, new FailureFinalHandler());
                $this->logger->info($exception->getMessage());

                return $result->getMessage();
            } catch (Throwable $exception) {
                $exception = new JobFailureException($message, $exception);
                $this->logger->error($exception->getMessage());
                throw $exception;
            }
        }
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
