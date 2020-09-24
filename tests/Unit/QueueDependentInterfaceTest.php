<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Psr\Log\NullLogger;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\PayloadFactory;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\QueueDependentInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class QueueDependentInterfaceTest extends TestCase
{
    public function driverProvider(): array
    {
        $dependent = new class() implements QueueDependentInterface, DriverInterface {
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

            public function push(MessageInterface $message): ?string
            {
            }

            public function subscribe(callable $handler): void
            {
            }

            public function canPush(MessageInterface $message): bool
            {
            }
        };
        $independent = new class() implements DriverInterface {
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

            public function push(MessageInterface $message): ?string
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
     * @dataProvider driverProvider
     *
     * @param DriverInterface $driver
     */
    public function testDependencyResolved(DriverInterface $driver): void
    {
        new Queue(
            $driver,
            $this->getEventDispatcher(),
            $this->getWorker(),
            $this->getLoop(),
            new NullLogger(),
            new PayloadFactory()
        );

        self::assertEquals($driver instanceof QueueDependentInterface, $driver->queue instanceof Queue);
    }
}
