<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Worker;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Middleware\Worker\WorkerHandlerInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactory;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class WorkerMiddlewareFactoryTest extends TestCase
{
    public function testRejectsNonCallableDefinition(): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);

        (new WorkerMiddlewareFactory(new SimpleContainer()))->createWorkerMiddleware(42);
    }

    public function testCallableReturningMiddlewareIsDelegated(): void
    {
        $workerMiddleware = new class implements WorkerMiddlewareInterface {
            public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest
            {
                return $request->withMessage(new GenericMessage('middleware', null));
            }
        };
        $middleware = (new WorkerMiddlewareFactory(new SimpleContainer()))->createWorkerMiddleware(
            static function () use ($workerMiddleware): WorkerMiddlewareInterface {
                return $workerMiddleware;
            },
        );
        $handler = new class implements WorkerHandlerInterface {
            public function handleWorker(WorkerRequest $request): WorkerRequest
            {
                return $request;
            }
        };

        $result = $middleware->processWorker(new WorkerRequest(new GenericMessage('start', null), 'queue'), $handler);

        self::assertSame('middleware', $result->getMessage()->getType());
    }

    public function testCallableReturningInvalidResponseIsRejected(): void
    {
        $middleware = (new WorkerMiddlewareFactory(new SimpleContainer()))->createWorkerMiddleware(
            static fn(): object => new stdClass(),
        );
        $handler = new class implements WorkerHandlerInterface {
            public function handleWorker(WorkerRequest $request): WorkerRequest
            {
                return $request;
            }
        };

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processWorker(new WorkerRequest(new GenericMessage('start', null), 'queue'), $handler);
    }
}
