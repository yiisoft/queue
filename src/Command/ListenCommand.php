<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;

#[AsCommand(
    'queue:listen',
    'Listens the queue and executes messages as they come. Needs to be stopped manually.',
)]
final class ListenCommand extends Command
{
    public function __construct(
        private readonly QueueConsumerProviderInterface $queueProvider,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'queue',
            InputArgument::OPTIONAL,
            'Queue name to connect to',
            QueueConsumerProviderInterface::DEFAULT_QUEUE,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = (string) $input->getArgument('queue');

        $this->queueProvider->getConsumer($queueName)->listen();

        return Command::SUCCESS;
    }
}
