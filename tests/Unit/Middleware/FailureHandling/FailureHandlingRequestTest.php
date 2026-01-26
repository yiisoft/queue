<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling;

use Exception;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\TestCase;

final class FailureHandlingRequestTest extends TestCase
{
    public function testImmutable(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $request1 = new FailureHandlingRequest(
            new Message('test', null),
            new Exception('exception 1'),
            $queue,
        );
        $request2 = $request1->withQueue($queue);
        $request3 = $request1->withException(new Exception('exception 2'));
        $request4 = $request1->withMessage(new Message('test2', null));

        $this->assertNotSame($request1, $request2);

        $this->assertNotSame($request1, $request3);
        $this->assertEquals($request1->getException()->getMessage(), 'exception 1');
        $this->assertEquals($request3->getException()->getMessage(), 'exception 2');

        $this->assertNotSame($request1, $request4);
        $this->assertEquals($request1->getMessage()->getHandlerName(), 'test');
        $this->assertEquals($request4->getMessage()->getHandlerName(), 'test2');
    }
}
