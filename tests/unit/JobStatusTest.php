<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class JobStatusTest extends TestCase
{
    public function getStatusPairs(): array
    {
        return [
            'waiting' => [
                'waiting',
                'isWaiting',
                [
                    'isReserved',
                    'isDone',
                ],
            ],
            'reserved' => [
                'reserved',
                'isReserved',
                [
                    'isWaiting',
                    'isDone',
                ],
            ],
            'done' => [
                'done',
                'isDone',
                [
                    'isWaiting',
                    'isReserved',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getStatusPairs
     */
    public function testInstanceValue(string $statusName, string $positiveMethod, array $negatives): void
    {
        $status = JobStatus::$statusName();

        $this->assertTrue($status->$positiveMethod(), "$positiveMethod must be true for status $statusName");
        foreach ($negatives as $negative) {
            $this->assertFalse($status->$negative(), "$negative must be false for status $statusName");
        }
    }
}
