<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Stubs;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Stubs\StubWorker;

final class StubWorkerTest extends TestCase
{
    public function testBase(): void
    {
        $worker = new StubWorker();

        $sourceMessage = new GenericMessage('test', 42);

        $message = $worker->process($sourceMessage, $this->createMock(QueueInterface::class));

        $this->assertSame($sourceMessage, $message);
        $this->assertSame('test', $message->getType());
        $this->assertSame(42, $message->getPayload());
        $this->assertSame([], $message->getMeta());
    }
}
