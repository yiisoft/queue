<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Command\ListenCommand;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Psr\Log\NullLogger;

final class ListenCommandTest extends TestCase
{
    public function testListensSelectedConsumer(): void
    {
        $processed = 0;
        $adapter = new InMemoryAdapter();
        $adapter->push(new GenericMessage('test', null));
        $consumer = $this->consumer($adapter, static function () use (&$processed): void {
            $processed++;
        });
        $command = new ListenCommand(new PredefinedQueueProvider(['queue' => ['consumer' => $consumer]]));

        self::assertSame(0, $command->run(new StringInput('queue'), $this->createMock(OutputInterface::class)));
        self::assertSame(1, $processed);
    }

    private function consumer(InMemoryAdapter $adapter, callable $handler): QueueConsumer
    {
        $container = new SimpleContainer();
        $factory = new CallableFactory($container);
        $worker = new Worker(
            new NullLogger(),
            new ConsumeMiddlewareDispatcher(new ConsumeMiddlewareFactory($container, $factory)),
            new FailureMiddlewareDispatcher(new FailureMiddlewareFactory($container, $factory), []),
            new HandlerResolver(['test' => $handler], $container),
        );

        return new QueueConsumer($worker, new SimpleLoop(), new NullLogger(), $adapter);
    }
}
