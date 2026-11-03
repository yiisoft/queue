<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Middleware\Worker\WorkerHandlerInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;

final class DispatcherCachingTest extends TestCase
{
    public function testPushMiddlewareIsCreatedOnlyOnceForRepeatedDispatches(): void
    {
        $factory = $this->createMock(PushMiddlewareFactoryInterface::class);
        $factoryCalls = 0;
        $factory->expects(self::once())->method('createPushMiddleware')->willReturnCallback(
            function () use (&$factoryCalls): PushMiddlewareInterface {
                $factoryCalls++;

                return new class implements PushMiddlewareInterface {
                    public function processPush(PushRequest $request, PushHandlerInterface $handler): PushRequest
                    {
                        return $handler->handlePush($request);
                    }
                };
            },
        );
        $final = new class implements PushHandlerInterface {
            public function handlePush(PushRequest $request): PushRequest
            {
                return $request;
            }
        };
        $dispatcher = new PushMiddlewareDispatcher($factory, ['definition'], $final);
        self::assertSame(0, $factoryCalls);
        $request = new PushRequest(new GenericMessage('test', null), 'queue');

        $dispatcher->dispatch($request);
        self::assertSame(1, $factoryCalls);

        $dispatcher->dispatch($request);
        self::assertSame(1, $factoryCalls);
    }

    public function testWorkerMiddlewareIsCreatedOnlyOnceForRepeatedDispatches(): void
    {
        $factory = $this->createMock(WorkerMiddlewareFactoryInterface::class);
        $factoryCalls = 0;
        $factory->expects(self::once())->method('createWorkerMiddleware')->willReturnCallback(
            function () use (&$factoryCalls): WorkerMiddlewareInterface {
                $factoryCalls++;

                return new class implements WorkerMiddlewareInterface {
                    public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest
                    {
                        return $handler->handleWorker($request);
                    }
                };
            },
        );
        $final = new class implements WorkerHandlerInterface {
            public function handleWorker(WorkerRequest $request): WorkerRequest
            {
                return $request;
            }
        };
        $dispatcher = new WorkerMiddlewareDispatcher($factory, ['definition'], $final);
        self::assertSame(0, $factoryCalls);
        $request = new WorkerRequest(new GenericMessage('test', null), 'queue');

        $dispatcher->dispatch($request);
        self::assertSame(1, $factoryCalls);

        $dispatcher->dispatch($request);
        self::assertSame(1, $factoryCalls);
    }
}
