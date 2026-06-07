<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use Yiisoft\Queue\MessageStatus;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Stubs\StubQueue;

final class QueueCollectorTest extends AbstractCollectorTestCase
{
    private GenericMessage $pushMessage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pushMessage = new GenericMessage('task', ['id' => 500]);
    }

    /**
     * @param QueueCollector $collector
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collectStatus('12345', MessageStatus::DONE, 'status.php:11');
        $collector->collectPush('chan1', $this->pushMessage, 'push.php:21');
        $collector->collectPush('chan2', $this->pushMessage, 'push.php:31');
        $collector->collectWorkerProcessing(
            $this->pushMessage,
            new StubQueue('chan1'),
        );
        $collector->collectWorkerProcessing(
            $this->pushMessage,
            new StubQueue('chan1'),
        );
        $collector->collectWorkerProcessing(
            $this->pushMessage,
            new StubQueue('chan2'),
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
                    'line' => 'push.php:21',
                ],
            ],
            'chan2' => [
                [
                    'message' => $this->pushMessage,
                    'line' => 'push.php:31',
                ],
            ],
        ], $pushes);
        $this->assertEquals([
            [
                'id' => '12345',
                'status' => 'done',
                'line' => 'status.php:11',
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
            $processingMessages,
        );
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        [
            'countPushes' => $countPushes,
            'countStatuses' => $countStatuses,
            'countProcessingMessages' => $countProcessingMessages,
        ] = $data;

        $this->assertEquals(2, $countPushes);
        $this->assertEquals(1, $countStatuses);
        $this->assertEquals(3, $countProcessingMessages);
    }
}
