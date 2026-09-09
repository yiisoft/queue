<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\QueueProducerStatusInterface;

final class QueueProducerStatusProxy implements QueueProducerStatusInterface
{
    public function __construct(
        private readonly QueueProducerStatusInterface $status,
        private readonly QueueCollector $collector,
    ) {}

    public function status(string|int $id): MessageStatus
    {
        /** @psalm-var array{file: string, line: int} $stack */
        $stack = debug_backtrace()[0];
        $result = $this->status->status($id);
        $this->collector->collectStatus((string) $id, $result, $stack['file'] . ':' . $stack['line']);
        return $result;
    }
}
