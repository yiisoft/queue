<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use Throwable;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Queue;

final class JobFailure implements StoppableEventInterface
{
    private bool $stop = false;
    private bool $throw = true;
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

    public function shouldThrowException(): bool
    {
        return $this->throw;
    }

    public function preventThrowing(): void
    {
        $this->throw = false;
    }

    public function stopPropagation(): void
    {
        $this->stop = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stop;
    }
}
