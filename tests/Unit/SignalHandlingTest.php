<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Command\ListenAllCommand;
use Yiisoft\Queue\Command\RunCommand;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\WorkerInterface;

use const SIGCONT;
use const SIGHUP;
use const SIGINT;
use const SIGTERM;
use const SIGTSTP;

#[RequiresPhpExtension('pcntl')]
#[RequiresPhpExtension('posix')]
final class SignalHandlingTest extends TestCase
{
    private array $previousHandlers = [];
    private bool $previousAsyncSignals;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([SIGHUP, SIGINT, SIGTERM, SIGTSTP, SIGCONT] as $signal) {
            $this->previousHandlers[$signal] = pcntl_signal_get_handler($signal);
        }
        $this->previousAsyncSignals = pcntl_async_signals(true);
    }

    protected function tearDown(): void
    {
        foreach ($this->previousHandlers as $signal => $handler) {
            pcntl_signal($signal, $handler);
        }
        pcntl_async_signals($this->previousAsyncSignals);

        parent::tearDown();
    }

    #[DataProvider('consumerMethodProvider')]
    public function testShutdownFinishesCurrentMessageAndLeavesNextMessageQueued(string $method): void
    {
        $applicationSignals = [];
        pcntl_signal(SIGTERM, static function (int $signal) use (&$applicationSignals): void {
            $applicationSignals[] = $signal;
        });
        $adapter = new InMemoryAdapter();
        $producer = $this->createQueue($adapter);
        $first = IdEnvelope::fromMessage($producer->push(new GenericMessage('stop', null)));
        $second = IdEnvelope::fromMessage($producer->push(new GenericMessage('stop', null)));
        $finishedMessages = 0;
        $worker = $this->createMock(WorkerInterface::class);
        $worker->expects($this->once())->method('process')->willReturnCallback(
            static function () use (&$finishedMessages): void {
                posix_kill(getmypid(), SIGTERM);
                $finishedMessages++;
            },
        );
        $consumer = new QueueConsumer($worker, new SignalLoop(), new NullLogger(), $adapter);

        $consumer->$method();
        posix_kill(getmypid(), SIGTERM);

        self::assertSame(1, $finishedMessages);
        self::assertSame(MessageStatus::DONE, $adapter->status($first->getId()));
        self::assertSame(MessageStatus::WAITING, $adapter->status($second->getId()));
        self::assertSame([SIGTERM], $applicationSignals, 'Application handles signals again after consuming ends.');
    }

    #[DataProvider('consumerMethodProvider')]
    public function testProcessingFailureRestoresApplicationSignalHandling(string $method): void
    {
        $applicationSignals = [];
        pcntl_signal(SIGTERM, static function (int $signal) use (&$applicationSignals): void {
            $applicationSignals[] = $signal;
        });
        $adapter = new InMemoryAdapter();
        $producer = $this->createQueue($adapter);
        $message = IdEnvelope::fromMessage($producer->push(new GenericMessage('failing', null)));
        $failure = new RuntimeException('Worker failed.');
        $worker = $this->createMock(WorkerInterface::class);
        $worker->method('process')->willThrowException($failure);
        $consumer = new QueueConsumer($worker, new SignalLoop(), new NullLogger(), $adapter);

        try {
            $consumer->$method();
            self::fail('The processing failure must reach the caller.');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }
        posix_kill(getmypid(), SIGTERM);

        self::assertSame([SIGTERM], $applicationSignals);
        self::assertSame(MessageStatus::WAITING, $adapter->status($message->getId()));
    }

    public static function consumerMethodProvider(): iterable
    {
        yield 'run' => ['run'];
        yield 'listen' => ['listen'];
    }

    /**
     * @param class-string<RunCommand>|class-string<ListenAllCommand> $commandClass
     */
    #[DataProvider('commandProvider')]
    public function testSignalBetweenQueuesStopsBeforeTheNextMessage(string $commandClass): void
    {
        $applicationSignals = [];
        pcntl_signal(SIGTERM, static function (int $signal) use (&$applicationSignals): void {
            $applicationSignals[] = $signal;
        });
        $loop = new SignalLoop();
        $firstAdapter = new InMemoryAdapter();
        $firstMessage = IdEnvelope::fromMessage(
            $this->createQueue($firstAdapter)->push(new GenericMessage('stop', null)),
        );
        $secondAdapter = new InMemoryAdapter();
        $secondMessage = IdEnvelope::fromMessage(
            $this->createQueue($secondAdapter)->push(new GenericMessage('later', null)),
        );
        $firstWorker = $this->createMock(WorkerInterface::class);
        $firstWorker->expects($this->once())->method('process');
        $firstConsumer = new QueueConsumer($firstWorker, $loop, new NullLogger(), $firstAdapter);
        $consumerAtQueueBoundary = $this->createMock(QueueConsumerInterface::class);
        $consumerAtQueueBoundary->method('run')->willReturnCallback(static function () use ($firstConsumer): int {
            $count = $firstConsumer->run();
            posix_kill(getmypid(), SIGTERM);

            return $count;
        });
        $secondWorker = $this->createMock(WorkerInterface::class);
        $secondWorker->expects($this->never())->method('process');
        $provider = new PredefinedQueueProvider([
            'first' => ['consumer' => $consumerAtQueueBoundary],
            'second' => ['consumer' => new QueueConsumer($secondWorker, $loop, new NullLogger(), $secondAdapter)],
        ]);
        $command = new $commandClass($provider, $loop);
        $input = new ArrayInput([], $command->getNativeDefinition());

        $result = $command->run($input, new BufferedOutput());
        posix_kill(getmypid(), SIGTERM);

        self::assertSame(0, $result);
        self::assertSame(MessageStatus::DONE, $firstAdapter->status($firstMessage->getId()));
        self::assertSame(MessageStatus::WAITING, $secondAdapter->status($secondMessage->getId()));
        self::assertSame([SIGTERM], $applicationSignals);
    }

    /**
     * @param class-string<RunCommand>|class-string<ListenAllCommand> $commandClass
     */
    #[DataProvider('commandProvider')]
    public function testCommandFailureRestoresApplicationSignalHandling(string $commandClass): void
    {
        $applicationSignals = [];
        pcntl_signal(SIGTERM, static function (int $signal) use (&$applicationSignals): void {
            $applicationSignals[] = $signal;
        });
        $adapter = new InMemoryAdapter();
        $this->createQueue($adapter)->push(new GenericMessage('failing', null));
        $failure = new RuntimeException('Worker failed.');
        $worker = $this->createMock(WorkerInterface::class);
        $worker->method('process')->willThrowException($failure);
        $loop = new SignalLoop();
        $consumer = new QueueConsumer($worker, $loop, new NullLogger(), $adapter);
        $command = new $commandClass(new PredefinedQueueProvider(['queue' => ['consumer' => $consumer]]), $loop);
        $input = new ArrayInput([], $command->getNativeDefinition());

        try {
            $command->run($input, new BufferedOutput());
            self::fail('The processing failure must reach the caller.');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }
        posix_kill(getmypid(), SIGTERM);

        self::assertSame([SIGTERM], $applicationSignals);
    }

    /**
     * @return iterable<string, array{class-string<RunCommand>|class-string<ListenAllCommand>}>
     */
    public static function commandProvider(): iterable
    {
        yield 'queue:run' => [RunCommand::class];
        yield 'queue:listen-all' => [ListenAllCommand::class];
    }
}
