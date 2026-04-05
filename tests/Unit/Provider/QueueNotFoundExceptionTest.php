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
        yield 'string' => ['queue1', 'queue1'];
        yield 'string-enum' => ['red', StringEnum::RED];
    }

    #[DataProvider('dataBase')]
    public function testBase(string $expectedName, mixed $name): void
    {
        $exception = new QueueNotFoundException($name);

        $this->assertStringContainsString(
            '"' . $expectedName . '" not found.',
            $exception->getMessage(),
        );
    }
}
