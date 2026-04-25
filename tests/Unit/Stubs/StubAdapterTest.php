<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Stubs;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Stubs\StubAdapter;

final class StubAdapterTest extends TestCase
{
    public function testBase(): void
    {
        $message = new Message('test', 42);
        $adapter = new StubAdapter();

        $this->assertSame($message, $adapter->push($message));
        $this->assertSame(MessageStatus::DONE, $adapter->status('test'));
        $adapter->runExisting(static fn() => null);
        $adapter->subscribe(static fn() => null);
    }
}
