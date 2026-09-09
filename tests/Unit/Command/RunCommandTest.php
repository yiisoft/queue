<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\AsyncQueueProducer;
use Yiisoft\Queue\Command\RunCommand;
use Yiisoft\Queue\DefaultQueue;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Cli\SimpleLoop;

final class RunCommandTest extends TestCase
{
    public function testRunsSelectedConsumer(): void
    {
        $processed = 0;
        $adapter = new InMemoryAdapter();
        $adapter->push(new GenericMessage('test', null));
        $adapter->push(new GenericMessage('test', null));
        $consumer = $this->consumer($adapter, static function () use (&$processed): void {
            $processed++;
        });
        $command = new RunCommand(new PredefinedQueueProvider(['queue' => ['consumer' => $consumer]]));
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('write')->with('Processing queue queue... ');
        $output->expects($this->once())->method('writeln')->with('Messages processed: 1.');

        self::assertSame(0, $command->run(new StringInput('queue --limit=1'), $output));
        self::assertSame(1, $processed);
    }

    public function testDefaultRunSkipsProducerOnlyQueues(): void
    {
        $adapter = new InMemoryAdapter();
        $processed = 0;
        $consumer = $this->consumer($adapter, static function () use (&$processed): void {
            $processed++;
        });
        $command = new RunCommand(new PredefinedQueueProvider([
            'producer' => ['producer' => new AsyncQueueProducer(new NullLogger(), new PushMiddlewareConfig(new PushMiddlewareFactory(new SimpleContainer(), new CallableFactory(new SimpleContainer()))), new InMemoryAdapter())],
            DefaultQueue::NAME => ['consumer' => $consumer],
        ]));

        self::assertSame(0, $command->run(new StringInput(''), $this->createMock(OutputInterface::class)));
        self::assertSame(0, $processed);
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
