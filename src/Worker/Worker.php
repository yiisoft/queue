<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Queue\Exception\MessageFailureException;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeFinalHandler;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureFinalHandler;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class Worker implements WorkerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConsumeMiddlewareDispatcher $consumeMiddlewareDispatcher,
        private readonly FailureMiddlewareDispatcher $failureMiddlewareDispatcher,
        private readonly HandlerResolver $handlerResolver,
    ) {}

    /**
     * @throws Throwable
     */
    public function process(
        MessageInterface $message,
        string $queueName,
        ?QueueProducerInterface $retryProducer = null,
    ): MessageInterface {
        $messageId = IdEnvelope::fromMessage($message)->getId();
        if ($messageId === null) {
            $this->logger->info('Processing message without ID.');
        } else {
            $this->logger->info('Processing message #{message}.', ['message' => $messageId]);
        }

        $request = new ConsumeRequest($message, $queueName);
        try {
            $handler = $this->handlerResolver->resolve($message->getType());
            $finalHandler = new ConsumeFinalHandler($handler->handle(...));
            return $this->consumeMiddlewareDispatcher->dispatch($request, $finalHandler)->getMessage();
        } catch (Throwable $exception) {
            $request = new FailureHandlingRequest($request->getMessage(), $exception, $request->getQueueName(), $retryProducer);

            try {
                $result = $this->failureMiddlewareDispatcher->dispatch($request, new FailureFinalHandler());
                $this->logger->info($exception->getMessage());

                return $result->getMessage();
            } catch (Throwable $exception) {
                $exception = new MessageFailureException($message, $exception);
                $this->logger->error($exception->getMessage());
                throw $exception;
            }
        }
    }
}
