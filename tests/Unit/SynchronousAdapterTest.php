<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class SynchronousAdapterTest extends TestCase
{
    protected function needsRealAdapter(): bool
    {
        return true;
    }

    public function testNonIntegerId(): void
    {
        $queue = $this->getQueue();
        $message = new Message('simple', null);
        $queue->push($message);
        $id = $message->getId();
        $wrongId = "$id ";
        self::assertEquals(JobStatus::waiting(), $queue->status($wrongId));
    }

    public function testIdSetting(): void
    {
        $message = new Message('simple', []);
        $adapter = $this->getAdapter();
        $adapter->setQueue($this->createMock(Queue::class));

        $ids = [];
        $adapter->push($message);
        $ids[] = $message->getId();
        $adapter->push($message);
        $ids[] = $message->getId();
        $adapter->push($message);
        $ids[] = $message->getId();

        self::assertCount(3, array_unique($ids));
    }
}
