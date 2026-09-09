<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\AsyncQueueProducer;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Command\ListenAllCommand;
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

final class ListenAllCommandTest extends TestCase
{
    public function testReportsWhenNoConsumersAreConfigured(): void
    {
        $command = new ListenAllCommand(new PredefinedQueueProvider([]), $this->createMock(LoopInterface::class));
        $output = new BufferedOutput();

        self::assertSame(Command::SUCCESS, $command->run(new ArrayInput([], $command->getNativeDefinition()), $output));
        self::assertSame("No consumers are configured.\n", $output->fetch());
    }

    public function testRunsOnlyConsumerRolesByDefault(): void
    {
        $processed = 0;
        $adapter = new InMemoryAdapter();
        $adapter->push(new GenericMessage('test', null));
        $consumer = $this->consumer($adapter, static function () use (&$processed): void {
            $processed++;
        });
        $loop = $this->createMock(LoopInterface::class);
        $loop->method('canContinue')->willReturn(true, false);
        $command = new ListenAllCommand(new PredefinedQueueProvider([
            'producer' => ['producer' => new AsyncQueueProducer(new NullLogger(), new PushMiddlewareConfig(new PushMiddlewareFactory(new SimpleContainer(), new CallableFactory(new SimpleContainer()))), new InMemoryAdapter())],
            'consumer' => ['consumer' => $consumer],
        ]), $loop);
        $input = new ArrayInput([], $command->getNativeDefinition());
        $input->setOption('pause', 0);

        self::assertSame(0, $command->run($input, $this->createMock(OutputInterface::class)));
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
