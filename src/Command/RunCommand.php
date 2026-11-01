<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\ScopedLoopInterface;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;

#[AsCommand(
    'queue:run',
    'Runs all the existing messages in the given queues. Exits once messages are over.',
)]
final class RunCommand extends Command
{
    public function __construct(
        private readonly QueueConsumerProviderInterface $queueProvider,
        private readonly ?LoopInterface $loop = null,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'queue',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Queue name list to connect to.',
            [],
        )
            ->addOption(
                'limit',
                'm',
                InputOption::VALUE_REQUIRED,
                'Maximum number of messages to process in each queue. Default is 0 (no limits).',
                0,
            )
            ->addUsage('[queue1 [queue2 [...]]] --limit 100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string[] $queueNames */
        $queueNames = $input->getArgument('queue');
        if ($queueNames === []) {
            $queueNames = $this->queueProvider->getConsumerQueueNames();
        }

        $limit = (int) $input->getOption('limit');

        // One scope for all queues, so that a signal received between two of them doesn't reach the default handler.
        if ($this->loop instanceof ScopedLoopInterface) {
            $this->loop->start();
        }

        try {
            /** @var string $queueName */
            foreach ($queueNames as $queueName) {
                $queueConsumer = $this->queueProvider->getConsumer($queueName);

                $output->write("Processing queue $queueName... ");
                $count = $queueConsumer->run($limit);

                $output->writeln("Messages processed: $count.");
            }
        } finally {
            if ($this->loop instanceof ScopedLoopInterface) {
                $this->loop->finish();
            }
        }

        return Command::SUCCESS;
    }
}
