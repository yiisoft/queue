<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Events;

use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Queue;

class BeforeExecution implements BeforeExecutionInterface
{
    protected bool $stop = false;

    /**
     * @var Queue
     */
    private Queue $queue;
    /**
     * @var MessageInterface
     */
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
