<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Adapter;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Adapter\StubAdapter;
use Yiisoft\Queue\Message\Message;

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
}
