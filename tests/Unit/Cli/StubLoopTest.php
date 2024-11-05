<?php

declare(strict_types=1);

namespace Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Queue\Cli\StubLoop;
use Yiisoft\Queue\Tests\TestCase;

final class StubLoopTest extends TestCase
{
    public static function dataBase(): iterable
    {
        yield 'true' => [true];
        yield 'false' => [false];
    }

    #[DataProvider('dataBase')]
    public function testBase(bool $canContinue): void
    {
        $loop = new StubLoop($canContinue);

        $this->assertSame($canContinue, $loop->canContinue());
    }
}
