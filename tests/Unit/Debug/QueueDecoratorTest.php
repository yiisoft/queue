<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueConsumerDecorator;
use Yiisoft\Queue\Debug\QueueProducerDecorator;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\QueueProducerInterface;

final class QueueDecoratorTest extends TestCase
{
    public function testProducerDecoratorDelegatesAndCollects(): void
    {
        $message = new GenericMessage('test', null);
        $producer = $this->createMock(QueueProducerInterface::class);
        $producer->method('getName')->willReturn('queue');
        $producer->expects($this->once())->method('push')->with($message)->willReturn($message);
        $producer->expects($this->once())->method('status')->with('1')->willReturn(MessageStatus::WAITING);
        $collector = new QueueCollector();
        $collector->startup();
        $decorator = new QueueProducerDecorator($producer, $collector);
        self::assertSame($message, $decorator->push($message));
        self::assertSame(MessageStatus::WAITING, $decorator->status('1'));
        self::assertArrayHasKey('queue', $collector->getCollected()['pushes']);
    }

    public function testConsumerDecoratorDelegates(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('run')->with(2)->willReturn(1);
        $consumer->expects($this->once())->method('listen');
        $decorator = new QueueConsumerDecorator($consumer, new QueueCollector());
        self::assertSame(1, $decorator->run(2));
        $decorator->listen();
    }
}
