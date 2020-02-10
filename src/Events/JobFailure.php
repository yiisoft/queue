<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Events;

use Throwable;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Queue;

class JobFailure implements JobFailureInterface
{
    protected bool $stop = false;
    private Queue $queue;
    private MessageInterface $message;
    private Throwable $exception;

    public function __construct(Queue $queue, MessageInterface $message, Throwable $exception)
    {
        $this->queue = $queue;
        $this->message = $message;
        $this->exception = $exception;
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * @inheritDoc
     */
    public function isPropagationStopped(): bool
    {
        return $this->stop;
    }

    public function stopPropagation(): void
    {
        $this->stop = true;
    }
}
