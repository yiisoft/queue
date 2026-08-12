<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling;

use Exception;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\Tests\TestCase;

final class FailureHandlingRequestTest extends TestCase
{
    public function testImmutable(): void
    {
        $queue = $this->createMock(QueueProducerInterface::class);
        $request1 = new FailureHandlingRequest(
            new GenericMessage('test', null),
            new Exception('exception 1'),
            'test-queue',
            $queue,
        );
        $request2 = $request1->withQueueName('other-queue');
        $request3 = $request1->withException(new Exception('exception 2'));
        $request4 = $request1->withMessage(new GenericMessage('test2', null));

        $this->assertNotSame($request1, $request2);

        $this->assertNotSame($request1, $request3);
        $this->assertEquals($request1->getException()->getMessage(), 'exception 1');
        $this->assertEquals($request3->getException()->getMessage(), 'exception 2');

        $this->assertNotSame($request1, $request4);
        $this->assertEquals($request1->getMessage()->getType(), 'test');
        $this->assertEquals($request4->getMessage()->getType(), 'test2');
    }
}
