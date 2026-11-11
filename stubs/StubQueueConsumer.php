<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use Yiisoft\Queue\QueueConsumerInterface;

final class StubQueueConsumer implements QueueConsumerInterface
{
    public function run(int $max = 0): int
    {
        return 0;
    }

    public function listen(): void {}
}
