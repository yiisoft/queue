<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Event;

use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\Queue;

final class BeforePush
{
    private bool $stop = false;
    private Queue $queue;
    private PayloadInterface $payload;

    public function __construct(Queue $queue, PayloadInterface $payload)
    {
        $this->queue = $queue;
        $this->payload = $payload;
    }

    public function getPayload(): PayloadInterface
    {
        return $this->payload;
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
