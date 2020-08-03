<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Event;

use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Queue;

final class BeforePush
{
    private bool $stop = false;
    private Queue $queue;
    private MessageInterface $message;

    public function __construct(Queue $queue, MessageInterface $message)
    {
        $this->queue = $queue;
        $this->message = $message;
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function isExecutionStopped(): bool
    {
        return $this->stop;
    }

    public function stopExecution(): void
    {
        $this->stop = true;
    }
}
