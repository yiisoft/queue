<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Worker;

use PHPUnit\Framework\TestCase;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Worker\WorkerHandlerInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactory;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;

final class WorkerMiddlewareDispatcherTest extends TestCase
{
    public function testMiddlewareOrderAndShortCircuit(): void
    {
        $events = [];
        $factory = new WorkerMiddlewareFactory(new SimpleContainer(), new CallableFactory(new SimpleContainer()));
        $dispatcher = new WorkerMiddlewareDispatcher($factory, [
            static function (WorkerRequest $request, WorkerHandlerInterface $next) use (&$events): WorkerRequest {
                $events[] = 'first-before';
                $result = $next->handleWorker($request);
                $events[] = 'first-after';
                return $result;
            },
            static function (WorkerRequest $request, WorkerHandlerInterface $next) use (&$events): WorkerRequest {
                $events[] = 'second';
                return $request->withMessage(new GenericMessage('done', null));
            },
        ]);
        $finish = new class implements WorkerHandlerInterface {
            public function handleWorker(WorkerRequest $request): WorkerRequest
            {
                return $request->withMessage(new GenericMessage('finish', null));
            }
        };

        $result = $dispatcher->dispatch(new WorkerRequest(new GenericMessage('start', null), 'queue'), $finish);

        self::assertSame(['first-before', 'second', 'first-after'], $events);
        self::assertSame('done', $result->getMessage()->getType());
    }

    public function testRequestNormalizesQueueAndHasNoQueueMutator(): void
    {
        $request = new WorkerRequest(new GenericMessage('test', null), '7');
        self::assertSame('7', $request->getQueueName());
        self::assertFalse(method_exists($request, 'withQueueName'));
    }
}
