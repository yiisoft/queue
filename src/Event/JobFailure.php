<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use Throwable;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\QueueInterface;

final class JobFailure implements StoppableEventInterface
{
    private bool $stop = false;
    private bool $throw = true;
    private QueueInterface $queue;
    private MessageInterface $message;
    private Throwable $exception;

    public function __construct(QueueInterface $queue, MessageInterface $message, Throwable $exception)
    {
        $this->queue = $queue;
        $this->message = $message;
        $this->exception = $exception;
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function getQueue(): QueueInterface
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
