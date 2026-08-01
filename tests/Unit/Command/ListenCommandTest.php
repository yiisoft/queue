<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Command\ListenCommand;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\QueueConsumerInterface;

final class ListenCommandTest extends TestCase
{
    public function testListensSelectedConsumer(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('listen');
        $command = new ListenCommand(new PredefinedQueueProvider(['queue' => ['consumer' => $consumer]]));
        self::assertSame(0, $command->run(new StringInput('queue'), $this->createMock(OutputInterface::class)));
    }
}
