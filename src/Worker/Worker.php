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
use Yiisoft\Queue\Message\MessageHandlerInterface;
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
    public function __construct(
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

        $handlerClass = $message->getHandler();

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

        $request = new ConsumeRequest($message, $queue);
        $closure = fn (MessageInterface $message): mixed => $this->injector->invoke([$handler, 'handle'], [$message]);
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

    private function createConsumeHandler(Closure $handler): MessageHandlerConsumeInterface
    {
        return new ConsumeFinalHandler($handler);
    }

    private function createFailureHandler(): MessageFailureHandlerInterface
    {
        return new FailureFinalHandler();
    }
}
