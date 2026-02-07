<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Tests\Unit\Support\StringEnum;

final class QueueNotFoundExceptionTest extends TestCase
{
    public static function dataBase(): iterable
    {
        yield 'string' => ['channel1', 'channel1'];
        yield 'string-enum' => ['red', StringEnum::RED];
    }

    #[DataProvider('dataBase')]
    public function testBase(string $expectedChannel, mixed $channel): void
    {
        $exception = new QueueNotFoundException($channel);

        $this->assertStringContainsString(
            '"' . $expectedChannel . '" not found.',
            $exception->getMessage(),
        );
    }
}
