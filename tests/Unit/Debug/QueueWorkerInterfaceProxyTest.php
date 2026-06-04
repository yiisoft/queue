<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueWorkerInterfaceProxy;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Stubs\StubQueue;
use Yiisoft\Queue\Stubs\StubWorker;

final class QueueWorkerInterfaceProxyTest extends TestCase
{
    public function testProcessDelegatesToWorker(): void
    {
        $message = new GenericMessage('handler', 'data');
        $collector = new QueueCollector();
        $collector->startup();
        $proxy = new QueueWorkerInterfaceProxy(new StubWorker(), $collector);

        $result = $proxy->process($message, new StubQueue('chan'));

        self::assertSame($message, $result);

        $collected = $collector->getCollected();
        self::assertArrayHasKey('processingMessages', $collected);
        self::assertArrayHasKey('chan', $collected['processingMessages']);
        self::assertCount(1, $collected['processingMessages']['chan']);
        self::assertSame($message, $collected['processingMessages']['chan'][0]);
    }
}
