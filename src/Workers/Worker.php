<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Workers;

use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Yiisoft\Yii\Queue\Events\AfterExecution;
use Yiisoft\Yii\Queue\Events\BeforeExecution;
use Yiisoft\Yii\Queue\Events\JobFailure;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Queue;

final class Worker implements WorkerInterface
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
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
        $event = new BeforeExecution($queue, $message);

        try {
            $this->dispatcher->dispatch($event);

            if ($event->isPropagationStopped() === false) {
                $message->getJob()->execute();

                $event = new AfterExecution($queue, $message);
                $this->dispatcher->dispatch($event);
            }
        } catch (Throwable $exception) {
            $event = new JobFailure($queue, $message, $exception);
            $this->dispatcher->dispatch($event);

            if ($event->isPropagationStopped() === false) {
                throw $exception;
            }
        }
    }
}
