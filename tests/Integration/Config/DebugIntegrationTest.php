<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration\Config;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Debug\Middleware\PushDebugMiddleware;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueProducerStatusProviderProxy;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Middleware\Worker\WorkerHandlerInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;
use Yiisoft\Queue\Provider\QueueFactoryProvider;
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;
use Yiisoft\Queue\Provider\QueueProducerStatusProvider;
use Yiisoft\Queue\Provider\QueueProducerStatusProviderInterface;
use Yiisoft\Queue\AsyncQueueProducer;

use function array_keys;
use function is_object;
use function assert;

final class DebugIntegrationTest extends TestCase
{
    /** @dataProvider debugStates */
    public function testMergedConfiguration(bool $enabled): void
    {
        $params = require __DIR__ . '/../../../config/params.php';
        $userPush = $this->middleware('push-user');
        $userWorker = $this->middleware('worker-user');
        $params = ArrayHelper::merge($params, [
            'yiisoft/yii-debug' => ['enabled' => $enabled],
            'yiisoft/queue' => [
                'middlewares-push' => [$userPush],
                'middlewares-worker' => [$userWorker],
            ],
        ]);
        $di = (static function (array $params): array {
            return require __DIR__ . '/../../../config/di.php';
        })($params);

        $adapter = new class implements AdapterInterface {
            public int $statusCalls = 0;

            public function runExisting(callable $handlerCallback): void {}

            public function status(string|int $id): MessageStatus
            {
                $this->statusCalls++;
                return MessageStatus::NOT_FOUND;
            }

            public function push(MessageInterface $message): MessageInterface
            {
                return $message;
            }

            public function subscribe(callable $handlerCallback): void {}
        };
        $definitions = ArrayHelper::merge($di, [
            'config.adapter' => $adapter,
            QueueProducerProviderInterface::class => static fn(ContainerInterface $container): QueueProducerProviderInterface => new QueueFactoryProvider([
                ConfigQueue::MAIN->value => ['producer' => [
                    'class' => AsyncQueueProducer::class,
                    '__construct()' => ['adapter' => $adapter, 'queueName' => ConfigQueue::MAIN],
                ]],
            ], $container),
            LoggerInterface::class => new NullLogger(),
            QueueCollector::class => QueueCollector::class,
            QueueProducerStatusProvider::class => QueueProducerStatusProvider::class,
            QueueProducerStatusProviderInterface::class => $enabled
                ? static function (ContainerInterface $container): QueueProducerStatusProviderInterface {
                    $provider = $container->get(QueueProducerStatusProvider::class);
                    $collector = $container->get(QueueCollector::class);
                    assert($provider instanceof QueueProducerStatusProvider);
                    assert($collector instanceof QueueCollector);
                    return new QueueProducerStatusProviderProxy($provider, $collector);
                }
                : QueueProducerStatusProvider::class,
        ]);

        $container = new Container(ContainerConfig::create()->withDefinitions($definitions));
        $status = $container->get(QueueProducerStatusProviderInterface::class);
        self::assertInstanceOf($enabled ? QueueProducerStatusProviderProxy::class : QueueProducerStatusProvider::class, $status);
        /** @var array{yiisoft/yii-debug: array{trackedServices: array<string, mixed>}} $params */
        self::assertSame(
            [QueueProducerStatusProviderInterface::class => [QueueProducerStatusProviderProxy::class, QueueCollector::class]],
            $params['yiisoft/yii-debug']['trackedServices'],
        );

        $collector = $container->get(QueueCollector::class);
        $collector->startup();
        self::assertSame(0, $adapter->statusCalls);
        self::assertSame(MessageStatus::NOT_FOUND, $status->getStatus(ConfigQueue::MAIN)->status('missing'));
        /** @psalm-suppress DocblockTypeContradiction */
        self::assertSame(1, $adapter->statusCalls);
        self::assertSame(['queue'], $status->getStatusQueueNames());

        $push = $container->get(PushMiddlewareConfig::class);
        self::assertSame($enabled ? PushDebugMiddleware::class : $this->middlewareClass($push->commonMiddlewareDefinitions[0]), $this->middlewareClass($push->commonMiddlewareDefinitions[0]));
        self::assertCount($enabled ? 2 : 1, $push->commonMiddlewareDefinitions);
        self::assertSame($userPush, $push->commonMiddlewareDefinitions[$enabled ? 1 : 0]);
        $this->dispatchPush($push);

        $worker = $container->get(WorkerMiddlewareDispatcher::class);
        self::assertTrue($worker->hasMiddlewares());
        $this->dispatchWorker($worker);
        /** @var array{pushes: array<string, list<mixed>>, processingMessages: array<string, list<mixed>>} $collected */
        $collected = $collector->getCollected();
        self::assertSame($enabled ? ['queue'] : [], array_keys($collected['pushes']));
        self::assertSame($enabled ? ['queue'] : [], array_keys($collected['processingMessages']));
    }

    public static function debugStates(): iterable
    {
        yield 'disabled' => [false];
        yield 'enabled' => [true];
    }

    private function middleware(string $stage): object
    {
        return new class ($stage) implements PushMiddlewareInterface, WorkerMiddlewareInterface {
            public function __construct(private string $stage) {}

            public function processPush(PushRequest $request, PushHandlerInterface $handler): PushRequest
            {
                return $handler->handlePush($request);
            }

            public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest
            {
                return $handler->handleWorker($request);
            }
        };
    }

    private function middlewareClass(mixed $middleware): string
    {
        return is_object($middleware) ? $middleware::class : (string) $middleware;
    }

    private function dispatchPush(PushMiddlewareConfig $config): void
    {
        $handler = new class implements PushHandlerInterface {
            public function handlePush(PushRequest $request): PushRequest
            {
                return $request;
            }
        };
        (new PushMiddlewareDispatcher($config->middlewareFactory, $config->commonMiddlewareDefinitions, $handler))
            ->dispatch(new PushRequest(new GenericMessage('config', null), ConfigQueue::MAIN));
    }

    private function dispatchWorker(WorkerMiddlewareDispatcher $dispatcher): void
    {
        $handler = new class implements WorkerHandlerInterface {
            public function handleWorker(WorkerRequest $request): WorkerRequest
            {
                return $request;
            }
        };
        $dispatcher->dispatch(new WorkerRequest(new GenericMessage('worker', null), ConfigQueue::MAIN), $handler);
    }
}

enum ConfigQueue: string
{
    case MAIN = 'queue';
}
