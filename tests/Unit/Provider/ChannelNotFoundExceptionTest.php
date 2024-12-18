<?php

declare(strict_types=1);

namespace Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Queue\Provider\ChannelNotFoundException;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Tests\Unit\Support\StringEnum;

final class ChannelNotFoundExceptionTest extends TestCase
{
    public static function dataBase(): iterable
    {
        yield 'string' => ['channel1', 'channel1'];
        yield 'string-enum' => ['red', StringEnum::RED];
    }

    #[DataProvider('dataBase')]
    public function testBase(string $expectedChannelName, mixed $channel): void
    {
        $exception = new ChannelNotFoundException($channel);

        $this->assertSame(
            'Channel "' . $expectedChannelName . '" not found.',
            $exception->getMessage(),
        );
    }
}
