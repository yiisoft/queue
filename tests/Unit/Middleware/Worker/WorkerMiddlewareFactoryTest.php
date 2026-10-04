<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Worker;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Middleware\Worker\WorkerHandlerInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactory;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;

final class WorkerMiddlewareFactoryTest extends TestCase
{
    public function testExistingMiddlewareObjectIsReturned(): void
    {
        $middleware = new class implements WorkerMiddlewareInterface {
            public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest
            {
                return $request;
            }
        };

        self::assertSame($middleware, $this->factory()->createWorkerMiddleware($middleware));
    }

    public function testCallableReturningRequestIsSupported(): void
    {
        $request = $this->request();
        $handler = $this->createMock(WorkerHandlerInterface::class);
        $handler
            ->expects(self::once())
            ->method('handleWorker')
            ->with($request)
            ->willReturn($request);

        $middleware = $this->factory()->createWorkerMiddleware(
            static function (WorkerRequest $actual, WorkerHandlerInterface $actualHandler): WorkerRequest {
                $actualHandler->handleWorker($actual);

                return $actual->withQueueName('changed');
            },
        );

        $result = $middleware->processWorker($request, $handler);

        self::assertSame('changed', $result->getQueueName());
    }

    public function testCallableReturningMiddlewareIsSupported(): void
    {
        $middleware = $this->factory()->createWorkerMiddleware(static fn(): WorkerMiddlewareInterface => new class implements WorkerMiddlewareInterface {
            public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest
            {
                return $request->withQueueName('changed');
            }
        });

        self::assertSame('changed', $middleware->processWorker($this->request(), $this->handler())->getQueueName());
    }

    public static function invalidDefinitions(): array
    {
        return [
            'scalar' => [42],
            'unknown string' => ['unknown'],
            'invalid array' => [['not', 'a', 'definition']],
        ];
    }

    #[DataProvider('invalidDefinitions')]
    public function testInvalidDefinitionThrows(mixed $definition): void
    {
        $this->expectException(InvalidMiddlewareDefinitionException::class);

        $this->factory()->createWorkerMiddleware($definition);
    }

    public function testCallableReturningInvalidResultThrows(): void
    {
        $middleware = $this->factory()->createWorkerMiddleware(static fn(): string => 'invalid');

        $this->expectException(InvalidMiddlewareDefinitionException::class);
        $middleware->processWorker($this->request(), $this->handler());
    }

    private function factory(): WorkerMiddlewareFactory
    {
        return new WorkerMiddlewareFactory(new SimpleContainer());
    }

    private function request(): WorkerRequest
    {
        return new WorkerRequest(new GenericMessage('test', null), 'queue');
    }

    private function handler(): WorkerHandlerInterface
    {
        return new class implements WorkerHandlerInterface {
            public function handleWorker(WorkerRequest $request): WorkerRequest
            {
                return $request;
            }
        };
    }
}
