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
use Yiisoft\Queue\DefaultQueue;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;

use function count;

#[AsCommand('queue:consume', 'Consumes queued messages. Listens continuously unless --once is passed.')]
final class ConsumeCommand extends Command
{
    public function __construct(
        private readonly QueueConsumerProviderInterface $queueProvider,
        private readonly LoopInterface $loop,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->addArgument('queue', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Queue names to consume.')
            ->addOption(
                'once',
                null,
                InputOption::VALUE_NONE,
                'Process existing messages in one pass and exit. Without names, use all configured consumer queues.',
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Continuously poll queues. Without names, use all configured consumer queues. '
                    . 'Recommended for development only.',
            )
            ->addOption(
                'limit',
                'm',
                InputOption::VALUE_REQUIRED,
                'Maximum messages per queue in each pass with --once or --all. Default: 0 (no limit).',
            )
            ->addOption(
                'pause',
                'p',
                InputOption::VALUE_REQUIRED,
                'Seconds between idle passes with --all without --once. Default: 1.',
            )
            ->addUsage('[queue]')
            ->addUsage('--once [queue1 [queue2 [...]]] --limit=100')
            ->addUsage('--all [queue1 [queue2 [...]]] --pause=1 --limit=100')
            ->setHelp(
                'Without queue names, listen to ' . DefaultQueue::NAME . '. '
                    . '--once and --all instead use all configured consumer queues. '
                    . '--once takes precedence over --all.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string[] $queueNames */
        $queueNames = $input->getArgument('queue');
        $once = (bool) $input->getOption('once');
        $all = (bool) $input->getOption('all');
        $limit = $input->getOption('limit');
        $pauseSeconds = $input->getOption('pause');

        if (!$once && !$all && $limit !== null) {
            $output->writeln('<error>--limit requires --once or --all.</error>');

            return Command::INVALID;
        }

        if ((!$all || $once) && $pauseSeconds !== null) {
            $output->writeln('<error>--pause requires --all without --once.</error>');

            return Command::INVALID;
        }

        if (!$once && !$all) {
            if (count($queueNames) > 1) {
                $output->writeln('<error>Use --all or --once to consume multiple queues.</error>');

                return Command::INVALID;
            }

            $this->queueProvider->getConsumer($queueNames[0] ?? DefaultQueue::NAME)->listen();

            return Command::SUCCESS;
        }

        if ($queueNames === []) {
            $queueNames = $this->queueProvider->getConsumerQueueNames();
        }

        $consumers = [];
        foreach ($queueNames as $queueName) {
            $consumers[$queueName] = $this->queueProvider->getConsumer($queueName);
        }

        if ($consumers === []) {
            $output->writeln('No consumers are configured.');

            return Command::SUCCESS;
        }

        $limit = (int) $limit;
        $pauseSeconds = (int) ($pauseSeconds ?? 1);
        if ($pauseSeconds < 0) {
            $pauseSeconds = 1;
        }

        if ($this->loop instanceof ScopedLoopInterface) {
            $this->loop->start();
        }

        try {
            while ($once || $this->loop->canContinue()) {
                $hasMessages = false;
                foreach ($consumers as $queueName => $consumer) {
                    if ($once) {
                        $output->write("Processing queue $queueName... ");
                    }

                    $count = $consumer->run($limit);
                    $hasMessages = $count > 0 || $hasMessages;

                    if ($once) {
                        $output->writeln("Messages processed: $count.");
                    }
                }

                if ($once) {
                    break;
                }

                if (!$hasMessages && $this->loop->canContinue()) {
                    /** @psalm-var 0|positive-int $pauseSeconds */
                    sleep($pauseSeconds);
                }
            }
        } finally {
            if ($this->loop instanceof ScopedLoopInterface) {
                $this->loop->finish();
            }
        }

        return Command::SUCCESS;
    }
}
