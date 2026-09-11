<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Yiisoft\Queue\Message\MessageInterface;

/**
 * Executes a message: runs it through the consume pipeline and, on failure, through the failure pipeline.
 */
interface WorkerInterface
{
    /**
     * @param string $queueName Logical execution queue name.
     */
    public function process(MessageInterface $message, string $queueName): void;
}
