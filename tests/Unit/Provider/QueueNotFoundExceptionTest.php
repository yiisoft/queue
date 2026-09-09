<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\Tests\Unit\Support\StringEnum;

final class QueueNotFoundExceptionTest extends TestCase
{
    /** @return iterable<string, array{string, string|StringEnum}> */
    public static function dataBase(): iterable
    {
        yield 'string' => ['queue1', 'queue1'];
        yield 'string-enum' => ['red', StringEnum::RED];
    }

    #[DataProvider('dataBase')]
    public function testBase(string $expectedName, string|StringEnum $name): void
    {
        $exception = new QueueNotFoundException($name);

        $this->assertStringContainsString(
            '"' . $expectedName . '" not found.',
            $exception->getMessage(),
        );
    }
}
