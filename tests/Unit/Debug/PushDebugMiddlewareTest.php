<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Queue\AsyncQueueProducer;
use Yiisoft\Queue\Debug\Middleware\PushDebugMiddleware;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class PushDebugMiddlewareTest extends TestCase
{
    public function testCollectsReturnedMessageAndEntryQueue(): void
    {
        $collector = new QueueCollector();
        $collector->startup();
        $message = new GenericMessage('in', null);
        $returned = new GenericMessage('out', null);
        $handler = new class ($returned) implements PushHandlerInterface {
            public function __construct(private PushRequest|GenericMessage $returned) {}

            public function handlePush(PushRequest $request): PushRequest
            {
                return $request->withMessage($this->returned instanceof PushRequest ? $this->returned->getMessage() : $this->returned);
            }
        };

        $result = (new PushDebugMiddleware($collector))->processPush(new PushRequest($message, 'entry'), $handler);

        self::assertSame($returned, $result->getMessage());
        /** @var array{pushes: array<string, list<array{message: MessageInterface, line: string}>>} $collected */
        $collected = $collector->getCollected();
        self::assertSame($returned, $collected['pushes']['entry'][0]['message']);
        self::assertSame(1, $collector->getSummary()['countPushes']);
    }

    public function testCollectsExternalPushCallerSourceFrame(): void
    {
        $collector = new QueueCollector();
        $collector->startup();
        $middleware = new PushDebugMiddleware($collector);
        $request = new PushRequest(new GenericMessage('test', null), 'queue');
        $handler = new class implements PushHandlerInterface {
            public function handlePush(PushRequest $request): PushRequest
            {
                return $request;
            }
        };

        $this->callPushFromExternalCaller($middleware, $request, $handler);

        /** @var array{pushes: array<string, list<array{message: MessageInterface, line: string}>>} $collected */
        $collected = $collector->getCollected();
        $line = $collected['pushes']['queue'][0]['line'];
        self::assertMatchesRegularExpression('/PushDebugMiddlewareTest\\.php:\\d+$/', $line);
        self::assertStringNotContainsString('PushMiddlewareStack.php', $line);
        self::assertStringNotContainsString('AsyncQueueProducer.php', $line);
    }

    public function testCollectsCallerSourceThroughAsyncProducer(): void
    {
        $collector = new QueueCollector();
        $collector->startup();
        $container = new SimpleContainer([QueueCollector::class => $collector]);
        $producer = new AsyncQueueProducer(
            new NullLogger(),
            new PushMiddlewareConfig(
                new PushMiddlewareFactory($container),
                [new PushDebugMiddleware($collector)],
            ),
            new InMemoryAdapter(),
        );

        $this->pushFromExternalCaller($producer);

        /** @var array{pushes: array<string, list<array{message: MessageInterface, line: string}>>} $collected */
        $collected = $collector->getCollected();
        $line = $collected['pushes']['yii-queue'][0]['line'];
        self::assertMatchesRegularExpression('/PushDebugMiddlewareTest\\.php:\\d+$/', $line);
        self::assertStringNotContainsString('PushMiddlewareStack.php', $line);
        self::assertStringNotContainsString('AsyncQueueProducer.php', $line);
    }

    public function testShortCircuitIsCollectedAndExceptionIsNot(): void
    {
        $collector = new QueueCollector();
        $collector->startup();
        $middleware = new PushDebugMiddleware($collector);
        $message = new GenericMessage('test', null);
        $shortCircuit = new class implements PushHandlerInterface {
            public function handlePush(PushRequest $request): PushRequest
            {
                return $request;
            }
        };
        $middleware->processPush(new PushRequest($message, 'queue'), $shortCircuit);
        self::assertSame(1, $collector->getSummary()['countPushes']);

        $failing = new class implements PushHandlerInterface {
            public function handlePush(PushRequest $request): PushRequest
            {
                throw new RuntimeException('failed');
            }
        };
        try {
            $middleware->processPush(new PushRequest($message, 'queue'), $failing);
        } catch (RuntimeException) {
        }
        self::assertSame(1, $collector->getSummary()['countPushes']);
    }

    public function testInactiveCollectorDoesNotRecord(): void
    {
        $collector = new QueueCollector();
        $handler = new class implements PushHandlerInterface {
            public function handlePush(PushRequest $request): PushRequest
            {
                return $request;
            }
        };
        (new PushDebugMiddleware($collector))->processPush(new PushRequest(new GenericMessage('test', null), 'queue'), $handler);
        self::assertSame([], $collector->getCollected());
    }

    private function pushFromExternalCaller(AsyncQueueProducer $producer): void
    {
        $producer->push(new GenericMessage('test', null));
    }

    /**
     * Keep the helper calls between the middleware and this test's call site.
     */
    private function callPushFromExternalCaller(
        PushDebugMiddleware $middleware,
        PushRequest $request,
        PushHandlerInterface $handler,
    ): void {
        $this->callPushFromPipeline($middleware, $request, $handler);
    }

    private function callPushFromPipeline(
        PushDebugMiddleware $middleware,
        PushRequest $request,
        PushHandlerInterface $handler,
    ): void {
        $this->callPushFromStack($middleware, $request, $handler);
    }

    private function callPushFromStack(
        PushDebugMiddleware $middleware,
        PushRequest $request,
        PushHandlerInterface $handler,
    ): void {
        $middleware->processPush($request, $handler);
    }
}
