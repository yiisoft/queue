<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Stubs;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Stubs\StubWorker;

final class StubWorkerTest extends TestCase
{
    public function testBase(): void
    {
        $this->expectNotToPerformAssertions();

        $worker = new StubWorker();
        $worker->process(new GenericMessage('test', 42), 'test-queue');
    }
}
