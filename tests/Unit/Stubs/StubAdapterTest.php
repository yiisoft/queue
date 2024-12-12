<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Stubs;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Stubs\StubAdapter;
use Yiisoft\Queue\Tests\Unit\Support\IntEnum;
use Yiisoft\Queue\Tests\Unit\Support\StringEnum;

final class StubAdapterTest extends TestCase
{
    public function testBase(): void
    {
        $message = new Message('test', 42);
        $adapter = new StubAdapter();

        $this->assertSame($message, $adapter->push($message));
        $this->assertTrue($adapter->status('test')->isDone());
        $this->assertNotSame($adapter, $adapter->withChannel('test'));
        $adapter->runExisting(static fn() => null);
        $adapter->subscribe(static fn() => null);
    }

    public static function dataWithChannel(): iterable
    {
        yield 'string' => ['test', 'test'];
        yield 'string-enum' => ['red', StringEnum::RED];
        yield 'integer-enum' => ['1', IntEnum::ONE];
    }

    #[DataProvider('dataWithChannel')]
    public function testWithChannel(string $expected, mixed $channel): void
    {
        $adapter = (new StubAdapter())->withChannel($channel);

        $this->assertSame($expected, $adapter->getChannelName());
    }
}
