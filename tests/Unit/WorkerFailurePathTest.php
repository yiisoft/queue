<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;
use Yiisoft\Test\Support\Log\SimpleLogger;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlerInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerHandlerInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;
use Yiisoft\Queue\Worker\Worker;

final class WorkerFailurePathTest extends TestCase
{
    public function testHandledWorkerFailureIsLoggedAndSentToFailurePipeline(): void
    {
        $failure = new RuntimeException('worker middleware failed');
        $workerFactory = $this->createMock(WorkerMiddlewareFactoryInterface::class);
        $workerFactory->method('createWorkerMiddleware')->willReturn(
            new class ($failure) implements WorkerMiddlewareInterface {
                public function __construct(private readonly RuntimeException $failure) {}

                public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest
                {
                    throw $this->failure;
                }
            },
        );
        $workerDispatcher = new WorkerMiddlewareDispatcher(
            $workerFactory,
            ['worker-middleware'],
            $this->createMock(WorkerHandlerInterface::class),
        );

        $failureFactory = $this->createMock(FailureMiddlewareFactoryInterface::class);
        $failureFactory->expects(self::once())
            ->method('createFailureMiddleware')
            ->willReturn(new class ($failure) implements FailureMiddlewareInterface {
                public function __construct(private readonly RuntimeException $expected) {}

                public function processFailure(FailureHandlingRequest $request, FailureHandlerInterface $handler): FailureHandlingRequest
                {
                    TestCase::assertSame($this->expected, $request->getException());

                    return $request;
                }
            });
        $failureDispatcher = new FailureMiddlewareDispatcher(
            $failureFactory,
            ['queue' => ['failure-middleware']],
        );
        $logger = new SimpleLogger();

        (new Worker($logger, $workerDispatcher, $failureDispatcher))
            ->process(new GenericMessage('test', null), 'queue');

        $messages = $logger->getMessages();
        self::assertCount(2, $messages);
        self::assertSame(LogLevel::INFO, $messages[1]['level']);
        self::assertSame('worker middleware failed', $messages[1]['message']);
    }
}
