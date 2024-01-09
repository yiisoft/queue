<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Exception\InvalidStatusException;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Tests\Unit\Support\TestJobStatus;

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

        self::assertTrue($status->$positiveMethod(), "$positiveMethod must be true for status $statusName");
        foreach ($negatives as $negative) {
            self::assertFalse($status->$negative(), "$negative must be false for status $statusName");
        }
    }

    public function testException(): void
    {
        try {
            TestJobStatus::withStatus(4)->isDone();
        } catch (InvalidStatusException $exception) {
            self::assertSame($exception::class, InvalidStatusException::class);
            self::assertSame($exception->getName(), 'Invalid job status provided');
            self::assertSame($exception->getStatus(), 4);
            $this->assertMatchesRegularExpression('/JobStatus::DONE/', $exception->getSolution());
        }
    }
}
