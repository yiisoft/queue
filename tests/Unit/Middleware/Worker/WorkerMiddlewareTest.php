<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Worker;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Worker\WorkerHandlerInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;

final class WorkerMiddlewareTest extends TestCase
{
    public function testMiddlewareRunsInOrder(): void
    {
        $calls = [];
        $factory = $this->createMock(WorkerMiddlewareFactoryInterface::class);
        $factory->method('createWorkerMiddleware')->willReturnCallback(
            static function (mixed $definition) use (&$calls): WorkerMiddlewareInterface {
                return new class ($definition, $calls) implements WorkerMiddlewareInterface {
                    private array $calls;

                    public function __construct(private string $name, array &$calls)
                    {
                        $this->calls = & $calls;
                    }

                    public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest
                    {
                        $this->calls[] = $this->name . ':before';
                        $result = $handler->handleWorker($request);
                        $this->calls[] = $this->name . ':after';

                        return $result;
                    }
                };
            },
        );
        $final = new class ($calls) implements WorkerHandlerInterface {
            private array $calls;

            public function __construct(array &$calls)
            {
                $this->calls = & $calls;
            }

            public function handleWorker(WorkerRequest $request): WorkerRequest
            {
                $this->calls[] = 'final';

                return $request;
            }
        };

        (new WorkerMiddlewareDispatcher($factory, ['one', 'two'], $final))
            ->dispatch(new WorkerRequest(new GenericMessage('test', null), 'queue'));

        self::assertSame(['one:before', 'two:before', 'final', 'two:after', 'one:after'], $calls);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $factory = $this->createMock(WorkerMiddlewareFactoryInterface::class);
        $factory->method('createWorkerMiddleware')->willReturn(new class implements WorkerMiddlewareInterface {
            public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest
            {
                return $request->withQueueName('short-circuited');
            }
        });
        $final = $this->createMock(WorkerHandlerInterface::class);
        $final->expects(self::never())->method('handleWorker');

        $result = (new WorkerMiddlewareDispatcher($factory, ['stop'], $final))
            ->dispatch(new WorkerRequest(new GenericMessage('test', null), 'queue'));

        self::assertSame('short-circuited', $result->getQueueName());
    }
}
