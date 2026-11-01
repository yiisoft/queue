<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Yiisoft\Queue\Command\ConsumeCommand;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\Tests\TestCase;

final class ConsumeCommandTest extends TestCase
{
    public function testOnceLimitsEachSelectedQueueAndLeavesOtherMessagesQueued(): void
    {
        $queues = [];
        $messages = [];
        $adapters = [];
        foreach (['first', 'second', 'other'] as $name) {
            $adapter = new InMemoryAdapter();
            $adapters[$name] = $adapter;
            $producer = $this->createQueue($adapter, $name);
            $messages[$name] = [
                IdEnvelope::fromMessage($producer->push(new GenericMessage('simple', null))),
                IdEnvelope::fromMessage($producer->push(new GenericMessage('simple', null))),
            ];
            $queues[$name] = ['consumer' => new QueueConsumer(
                $this->getWorker(),
                $this->getLoop(),
                new NullLogger(),
                $adapter,
                $name,
            )];
        }
        $command = new ConsumeCommand(new PredefinedQueueProvider($queues), $this->getLoop());
        $output = new BufferedOutput();

        $result = $command->run(new StringInput('--once first second --limit=1'), $output);

        self::assertSame(Command::SUCCESS, $result);
        self::assertSame(2, $this->executionTimes);
        foreach (['first', 'second'] as $name) {
            self::assertSame(MessageStatus::DONE, $adapters[$name]->status($messages[$name][0]->getId()));
            self::assertSame(MessageStatus::WAITING, $adapters[$name]->status($messages[$name][1]->getId()));
        }
        foreach ($messages['other'] as $message) {
            self::assertSame(MessageStatus::WAITING, $adapters['other']->status($message->getId()));
        }
        self::assertSame(
            "Processing queue first... Messages processed: 1.\nProcessing queue second... Messages processed: 1.\n",
            $output->fetch(),
        );
    }
}
