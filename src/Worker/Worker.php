<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Queue\Exception\MessageFailureException;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureFinalHandler;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Message\IdEnvelope;

final class Worker implements WorkerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WorkerMiddlewareDispatcher $workerMiddlewareDispatcher,
        private readonly FailureMiddlewareDispatcher $failureMiddlewareDispatcher,
    ) {}

    /**
     * @throws Throwable
     */
    public function process(MessageInterface $message, string $queueName): void
    {
        $messageId = IdEnvelope::fromMessage($message)->getId();
        if ($messageId === null) {
            $this->logger->info('Processing message without ID.');
        } else {
            $this->logger->info('Processing message #{message}.', ['message' => $messageId]);
        }

        $request = new WorkerRequest($message, $queueName);
        try {
            $this->workerMiddlewareDispatcher->dispatch($request);
        } catch (Throwable $exception) {
            $request = new FailureHandlingRequest($request->getMessage(), $exception, $request->getQueueName());

            try {
                $this->failureMiddlewareDispatcher->dispatch($request, new FailureFinalHandler());
                $this->logger->info($exception->getMessage());
            } catch (Throwable $exception) {
                $exception = new MessageFailureException($message, $exception);
                $this->logger->error($exception->getMessage());
                throw $exception;
            }
        }
    }
}
