<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Debug\Middleware\WorkerDebugMiddleware;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Worker\WorkerHandlerInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;
use RuntimeException;

final class WorkerDebugMiddlewareTest extends TestCase
{
    public function testRecordsBeforeHandlerIncludingResolutionException(): void
    {
        $collector = new QueueCollector();
        $collector->startup();
        $message = new GenericMessage('missing', null);
        $handler = new class implements WorkerHandlerInterface {
            public function handleWorker(WorkerRequest $request): WorkerRequest
            {
                throw new RuntimeException('missing handler');
            }
        };

        try {
            (new WorkerDebugMiddleware($collector))->processWorker(new WorkerRequest($message, 'queue'), $handler);
        } catch (RuntimeException) {
        }

        self::assertSame([$message], $collector->getCollected()['processingMessages']['queue']);
        self::assertSame(1, $collector->getSummary()['countProcessingMessages']);
    }

    public function testOneEventPerInvocationAndInactiveIsNoOp(): void
    {
        $collector = new QueueCollector();
        $collector->startup();
        $handler = new class implements WorkerHandlerInterface {
            public function handleWorker(WorkerRequest $request): WorkerRequest
            {
                return $request;
            }
        };
        $middleware = new WorkerDebugMiddleware($collector);
        $request = new WorkerRequest(new GenericMessage('test', null), 'queue');
        $middleware->processWorker($request, $handler);
        $middleware->processWorker($request, $handler);
        self::assertSame(2, $collector->getSummary()['countProcessingMessages']);

        $inactive = new QueueCollector();
        (new WorkerDebugMiddleware($inactive))->processWorker($request, $handler);
        self::assertSame([], $inactive->getCollected());
    }
}
