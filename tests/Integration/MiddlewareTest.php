<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Yii\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Yii\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\Integration\Support\TestMiddleware;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class MiddlewareTest extends TestCase
{
    public function testFullStackPush(): void
    {
        $stack = [
            'initial',
            'common 1',
            'common 2',
            'message 1',
            'message 2',
        ];

        $pushMiddlewareDispatcher = new PushMiddlewareDispatcher(
            new MiddlewareFactoryPush(
                $this->createMock(ContainerInterface::class),
                new CallableFactory(
                    $this->createMock(ContainerInterface::class)
                ),
            ),
            $this->createMock(EventDispatcherInterface::class),
            new TestMiddleware('common 1'),
            new TestMiddleware('common 2'),
        );
        $queue = new Queue(
            $this->createMock(WorkerInterface::class),
            $this->createMock(LoopInterface::class),
            $this->createMock(LoggerInterface::class),
            $pushMiddlewareDispatcher,
            new AdapterPushHandler(),
            new SynchronousAdapter(
                $this->createMock(WorkerInterface::class),
                $this->createMock(QueueInterface::class),
            ),
        );

        $message = new Message('test', ['initial']);
        $messagePushed = $queue->push(
            $message,
            new TestMiddleware('message 1'),
            new TestMiddleware('message 2'),
        );

        self::assertEquals($stack, $messagePushed->getData());
    }
}
