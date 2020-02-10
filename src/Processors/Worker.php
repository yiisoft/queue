<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Processors;

use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Yiisoft\Factory\Factory;
use Yiisoft\Yii\Queue\Events\AfterExecutionInterface;
use Yiisoft\Yii\Queue\Events\BeforeExecutionInterface;
use Yiisoft\Yii\Queue\Events\JobFailureInterface;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Queue;

final class Worker implements WorkerInterface
{
    private EventDispatcherInterface $dispatcher;
    private Factory $factory;

    public function __construct(EventDispatcherInterface $dispatcher, Factory $factory)
    {
        $this->dispatcher = $dispatcher;
        $this->factory = $factory;
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
        /** @var BeforeExecutionInterface $event */
        $event = $this->factory->create(BeforeExecutionInterface::class, [$queue, $message]);

        try {
            $this->dispatcher->dispatch($event);

            if ($event->isPropagationStopped() === false) {
                $message->getJob()->execute();

                $event = $this->factory->create(AfterExecutionInterface::class, [$queue, $message]);
                $this->dispatcher->dispatch($event);
            }
        } catch (Throwable $exception) {
            $event = $this->factory->create(JobFailureInterface::class, [$queue, $message, $exception]);
            $this->dispatcher->dispatch($event);

            throw $exception;
        }
    }
}
