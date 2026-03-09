<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Stubs;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\JobStatus;
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
        $this->assertSame(JobStatus::DONE, $adapter->status('test'));
        $adapter->runExisting(static fn() => null);
        $adapter->subscribe(static fn() => null);
    }
}
