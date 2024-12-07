<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Worker\WorkerInterface;

/**
 * Stub worker that does nothing.
 */
final class StubWorker implements WorkerInterface
{
    public function process(MessageInterface $message, QueueInterface $queue): MessageInterface
    {
        return $message;
    }
}
