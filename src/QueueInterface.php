<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;
use Yiisoft\Yii\Queue\Message\MessageInterface;

interface QueueInterface
{
    /**
     * Pushes a message into the queue.
     *
     * @param MessageInterface $message
     *
     * @throws BehaviorNotSupportedException
     */
    public function push(MessageInterface $message): void;

    /**
     * Execute all existing jobs and exit
     *
     * @param int $max
     */
    public function run(int $max = 0): void;

    /**
     * Listen to the queue and execute jobs as they come
     */
    public function listen(): void;

    /**
     * @param string $id A message id
     *
     * @return JobStatus
     * @throws InvalidArgumentException when there is no such id in the adapter
     *
     */
    public function status(string $id): JobStatus;

    public function withAdapter(AdapterInterface $adapter): QueueInterface;
}
