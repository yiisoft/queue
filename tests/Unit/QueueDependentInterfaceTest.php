<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Psr\Log\NullLogger;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\QueueDependentInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class QueueDependentInterfaceTest extends TestCase
{
    public function adapterProvider(): array
    {
        $dependent = new class() implements QueueDependentInterface, AdapterInterface {
            public ?Queue $queue = null;

            public function setQueue(Queue $queue): void
            {
                $this->queue = $queue;
            }

            public function nextMessage(): ?MessageInterface
            {
            }

            public function status(string $id): JobStatus
            {
            }

            public function push(MessageInterface $message): void
            {
            }

            public function subscribe(callable $handler): void
            {
            }

            public function canPush(MessageInterface $message): bool
            {
            }
        };
        $independent = new class() implements AdapterInterface {
            public ?Queue $queue = null;

            public function setQueue(Queue $queue): void
            {
                $this->queue = $queue;
            }

            public function nextMessage(): ?MessageInterface
            {
            }

            public function status(string $id): JobStatus
            {
            }

            public function push(MessageInterface $message): void
            {
            }

            public function subscribe(callable $handler): void
            {
            }

            public function canPush(MessageInterface $message): bool
            {
            }
        };

        return [
            [$dependent],
            [$independent],
        ];
    }

    /**
     * @dataProvider adapterProvider
     *
     * @param AdapterInterface $adapter
     */
    public function testDependencyResolved(AdapterInterface $adapter): void
    {
        new Queue(
            $adapter,
            $this->getEventDispatcher(),
            $this->getWorker(),
            $this->getLoop(),
            new NullLogger()
        );

        self::assertEquals($adapter instanceof QueueDependentInterface, $adapter->queue instanceof Queue);
    }
}
