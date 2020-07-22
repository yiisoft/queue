<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use Yiisoft\Yii\Queue\Tests\App\DelayableJob;
use Yiisoft\Yii\Queue\Tests\App\PrioritizedJob;
use Yiisoft\Yii\Queue\Tests\App\RetryablePayload;
use Yiisoft\Yii\Queue\Tests\App\SimplePayload;
use Yiisoft\Yii\Queue\Tests\TestCase;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;
use Yiisoft\Yii\Queue\Payload\PrioritisedPayloadInterface;
use Yiisoft\Yii\Queue\Payload\RetryablePayloadInterface;
use Yiisoft\Yii\Queue\Queue;

class SynchronousDriverTest extends TestCase
{
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
                DelayableJob::class,
                false,
            ],
            PrioritisedPayloadInterface::class => [
                PrioritizedJob::class,
                false,
            ],
            RetryablePayloadInterface::class => [
                RetryablePayload::class,
                true,
            ],
        ];
    }
}
