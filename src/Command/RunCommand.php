<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Provider\QueueProviderInterface;

#[AsCommand(
    'queue:run',
    'Runs all the existing messages in the given queues. Exits once messages are over.',
)]
final class RunCommand extends Command
{
    public function __construct(
        private readonly QueueProviderInterface $queueProvider,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'queue',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Queue name list to connect to.',
            $this->queueProvider->getNames(),
        )
            ->addOption(
                'maximum',
                'm',
                InputOption::VALUE_REQUIRED,
                'Maximum number of messages to process in each queue. Default is 0 (no limits).',
                0,
            )
            ->addUsage('[queue1 [queue2 [...]]] --maximum 100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $queue */
        foreach ($input->getArgument('queue') as $queue) {
            $output->write("Processing queue $queue... ");
            $count = $this->queueProvider
                ->get($queue)
                ->run((int) $input->getOption('maximum'));

            $output->writeln("Messages processed: $count.");
        }

        return 0;
    }
}
