<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\QueueFactory;
use Yiisoft\Queue\QueueFactoryInterface;

#[AsCommand('queue:run', 'Runs all the existing messages in the queue. Exits once messages are over.')]
final class RunCommand extends Command
{
    public function __construct(private QueueFactoryInterface $queueFactory)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'channel',
            InputArgument::OPTIONAL,
            'Queue channel name to connect to',
            QueueFactory::DEFAULT_CHANNEL_NAME
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->queueFactory
            ->get($input->getArgument('channel'))
            ->run();

        return 0;
    }
}
