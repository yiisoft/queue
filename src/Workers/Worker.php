<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Workers;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Yii\Queue\Events\AfterExecution;
use Yiisoft\Yii\Queue\Events\BeforeExecution;
use Yiisoft\Yii\Queue\Events\JobFailure;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Queue;

final class Worker implements WorkerInterface
{
    private EventDispatcherInterface $dispatcher;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    /**
     * @param MessageInterface $message
     *
     * @param Queue $queue
     *
     * @throws Throwable
     */
    public function process(MessageInterface $message, Queue $queue): void
    {
        $this->logger->debug('Start working with message #{message}', ['message' => $message->getId()]);
        $event = new BeforeExecution($queue, $message);

        try {
            $this->dispatcher->dispatch($event);

            if ($event->isExecutionStopped() === false) {
                $message->getJob()->execute();

                $event = new AfterExecution($queue, $message);
                $this->dispatcher->dispatch($event);
            } else {
                $this->logger->notice(
                    'Execution of message #{message} is stopped by an event handler',
                    ['message' => $message->getId()]
                );
            }
        } catch (Throwable $exception) {
            $this->logger->error(
                "Processing of message #{message} is stopped because of an exception:\n{exception}",
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
}
