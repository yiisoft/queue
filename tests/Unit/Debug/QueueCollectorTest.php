<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Debug;

use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;
use Yiisoft\Yii\Queue\Debug\QueueCollector;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Tests\App\DummyQueue;

final class QueueCollectorTest extends AbstractCollectorTestCase
{
    private Message $pushMessage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pushMessage = new Message('task', ['id' => 500]);
    }

    /**
     * @param CollectorInterface|QueueCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collectStatus('12345', JobStatus::done());
        $collector->collectPush('chan1', $this->pushMessage);
        $collector->collectPush('chan2', $this->pushMessage);
        $collector->collectWorkerProcessing(
            $this->pushMessage,
            new DummyQueue('chan1'),
        );
        $collector->collectWorkerProcessing(
            $this->pushMessage,
            new DummyQueue('chan1'),
        );
        $collector->collectWorkerProcessing(
            $this->pushMessage,
            new DummyQueue('chan2'),
        );
    }

    protected function getCollector(): CollectorInterface
    {
        return new QueueCollector();
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);
        [
            'pushes' => $pushes,
            'statuses' => $statuses,
            'processingMessages' => $processingMessages,
        ] = $data;

        $this->assertEquals([
            'chan1' => [
                [
                    'message' => $this->pushMessage,
                    'middlewares' => [],
                ],
            ],
            'chan2' => [
                [
                    'message' => $this->pushMessage,
                    'middlewares' => [],
                ],
            ],
        ], $pushes);
        $this->assertEquals([
            [
                'id' => '12345',
                'status' => 'done',
            ],
        ], $statuses);
        $this->assertEquals(
            [
                'chan1' => [
                    $this->pushMessage,
                    $this->pushMessage,
                ],
                'chan2' => [
                    $this->pushMessage,
                ],
            ],
            $processingMessages
        );
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        [
            'countPushes' => $countPushes,
            'countStatuses' => $countStatuses,
            'countProcessingMessages' => $countProcessingMessages,
        ] = $data['queue'];

        $this->assertEquals(2, $countPushes);
        $this->assertEquals(1, $countStatuses);
        $this->assertEquals(3, $countProcessingMessages);
    }
}
