<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use Yiisoft\Yii\Queue\Tests\App\DelayablePayload;
use Yiisoft\Yii\Queue\Tests\App\PrioritizedPayload;
use Yiisoft\Yii\Queue\Tests\App\QueueHandler;
use Yiisoft\Yii\Queue\Tests\App\RetryablePayload;
use Yiisoft\Yii\Queue\Tests\App\SimplePayload;
use Yiisoft\Yii\Queue\Tests\TestCase;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;
use Yiisoft\Yii\Queue\Payload\PrioritisedPayloadInterface;
use Yiisoft\Yii\Queue\Payload\AttemptsRestrictedPayloadInterface;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

class SynchronousDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $handlers = [
            'simple' => [QueueHandler::class, 'simple'],
            'retryable' => [QueueHandler::class, 'simple'],
        ];
        $this->container->get(WorkerInterface::class)->registerHandlers($handlers);
    }

    /**
     * @dataProvider getJobTypes
     *
     * @param string $class
     * @param bool $available
     */
    public function testJobType(string $class, bool $available): void
    {
        $queue = $this->container->get(Queue::class);
        $job = $this->container->get($class);

        if (!$available) {
            $this->expectException(PayloadNotSupportedException::class);
        }

        $id = $queue->push($job);

        if ($available) {
            $this->assertTrue($id >= 0);
        }
    }

    public static function getJobTypes(): array
    {
        return [
            'Simple job' => [
                SimplePayload::class,
                true,
            ],
            DelayablePayloadInterface::class => [
                DelayablePayload::class,
                false,
            ],
            PrioritisedPayloadInterface::class => [
                PrioritizedPayload::class,
                false,
            ],
            AttemptsRestrictedPayloadInterface::class => [
                RetryablePayload::class,
                true,
            ],
        ];
    }
}
