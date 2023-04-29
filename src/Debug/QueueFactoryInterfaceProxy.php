<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Debug;

use Yiisoft\Yii\Queue\QueueFactoryInterface;
use Yiisoft\Yii\Queue\QueueInterface;

final class QueueFactoryInterfaceProxy implements QueueFactoryInterface
{
    public function __construct(
        private QueueFactoryInterface $queueFactory,
        private QueueCollector $collector,
    ) {
    }

    public function get(string $channel = self::DEFAULT_CHANNEL_NAME): QueueInterface
    {
        $queue = $this->queueFactory->get($channel);

        return new QueueDecorator($queue, $this->collector);
    }
}
