<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Command;

use Throwable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\ScopedLoopInterface;
use Yiisoft\Queue\Cli\TerminalStatusReporter;
use Yiisoft\Queue\DefaultQueue;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;

use function count;
use function preg_replace;
use function sprintf;
use function substr;
use function trim;

#[AsCommand('queue:consume', 'Consumes queued messages. Listens continuously unless --once is passed.')]
final class ConsumeCommand extends Command
{
    public function __construct(
        private readonly QueueConsumerProviderInterface $queueProvider,
        private readonly LoopInterface $loop,
        private readonly ?TerminalStatusReporter $statusReporter = null,
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

        $statusReporter = $this->statusReporter ?? TerminalStatusReporter::forOutput($output);

        if (!$once && !$all) {
            if (count($queueNames) > 1) {
                $output->writeln('<error>Use --all or --once to consume multiple queues.</error>');

                return Command::INVALID;
            }

            $queueName = $queueNames[0] ?? DefaultQueue::NAME;
            $statusReporter->emit('working', sprintf('Listening on queue "%s"', $queueName));

            try {
                $this->queueProvider->getConsumer($queueName)->listen();
            } catch (Throwable $exception) {
                $statusReporter->emit('error', sprintf('Consume on queue "%s" failed', $queueName));

                throw $exception;
            }

            $statusReporter->emit('done', sprintf('Stopped listening on queue "%s"', $queueName));

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

        $statusReporter->emit('working', self::rootMessage($queueNames, $once));

        $processed = 0;
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

                    $statusId = self::statusId($queueName);
                    $statusReporter->emit('working', sprintf('Processing queue "%s"', $queueName), $statusId);

                    $count = $consumer->run($limit);
                    $hasMessages = $count > 0 || $hasMessages;
                    $processed += $count;

                    $statusReporter->emit('done', sprintf('Queue "%s": %d messages processed', $queueName, $count), $statusId);

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
        } catch (Throwable $exception) {
            $statusReporter->emit('error', 'Consume failed');

            throw $exception;
        } finally {
            if ($this->loop instanceof ScopedLoopInterface) {
                $this->loop->finish();
            }
        }

        $statusReporter->emit('done', $once
            ? sprintf('%d messages processed', $processed)
            : 'Stopped listening');

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $queueNames
     */
    private static function rootMessage(array $queueNames, bool $once): string
    {
        $count = count($queueNames);
        $description = $count === 1
            ? sprintf('queue "%s"', $queueNames[0])
            : sprintf('%d queues', $count);

        return $once
            ? sprintf('Consuming %s in one pass', $description)
            : sprintf('Listening on %s', $description);
    }

    /**
     * A task id for the queue in terminal status reports.
     *
     * Returns `null` when the queue name cannot be represented as a valid protocol id segment, in which case the
     * report falls back to the program-level record.
     */
    private static function statusId(string $queueName): ?string
    {
        $segment = trim((string) preg_replace('/[^A-Za-z0-9_.+-]+/', '-', $queueName), '-');

        if ($segment === '') {
            return null;
        }

        return 'queue/' . substr($segment, 0, 32);
    }
}
