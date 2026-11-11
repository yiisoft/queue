<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\ScopedLoopInterface;
use Yiisoft\Queue\Cli\TerminalStatusReporter;
use Yiisoft\Queue\Cli\TerminalStatusWriter;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\DefaultQueue;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;

use function count;
use function array_unique;
use function array_values;
use function sprintf;

#[AsCommand('queue:consume', 'Consumes queued messages. Listens continuously unless --once is passed.')]
final class ConsumeCommand extends Command
{
    public function __construct(
        private readonly QueueConsumerProviderInterface $queueProvider,
        private readonly LoopInterface $loop,
        private readonly ?TerminalStatusWriter $statusReporter = null,
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

        if (!$once && !$all && count($queueNames) > 1) {
            $output->writeln('<error>Use --all or --once to consume multiple queues.</error>');

            return Command::INVALID;
        }

        $statusReporter = $this->statusReporter ?? TerminalStatusReporter::forOutput($output);
        $activeId = null;
        $statusReporter->emit('working', 'Preparing queue consumers');

        try {
            if (!$once && !$all) {
                $queueName = $queueNames[0] ?? DefaultQueue::NAME;
                $activeId = 'queue-1';
                $statusReporter->emit('working', sprintf('Consuming queue "%s"', $queueName), $activeId);
                $this->queueProvider->getConsumer($queueName)->listen();
                $statusReporter->clear();
                $statusReporter->emit('idle', 'Queue consumer returned');

                return Command::SUCCESS;
            }

            if ($queueNames === []) {
                $queueNames = $this->queueProvider->getConsumerQueueNames();
            }
            $queueNames = array_values(array_unique($queueNames));
            $statusReporter->emit('working', self::rootMessage($queueNames, $once));

            // Use list positions for record ids; queue names can contain arbitrary characters or numeric strings.
            $consumers = [];
            foreach ($queueNames as $queueName) {
                $consumers[] = $this->queueProvider->getConsumer($queueName);
            }

            if ($consumers === []) {
                $output->writeln('No consumers are configured.');
                $statusReporter->emit('done', 'No consumers are configured.');

                return Command::SUCCESS;
            }

            $limit = (int) $limit;
            $pauseSeconds = (int) ($pauseSeconds ?? 1);
            if ($pauseSeconds < 0) {
                $pauseSeconds = 1;
            }

            $processed = 0;
            $failure = null;
            try {
                if ($this->loop instanceof ScopedLoopInterface) {
                    $this->loop->start();
                }
                while ($once || $this->loop->canContinue()) {
                    $hasMessages = false;
                    foreach ($consumers as $index => $consumer) {
                        $queueName = $queueNames[$index];
                        if ($once) {
                            $output->write("Processing queue $queueName... ");
                        }

                        $activeId = 'queue-' . ($index + 1);
                        $statusReporter->emit('working', sprintf('Processing queue "%s"', $queueName), $activeId);
                        $count = $consumer->run($limit);
                        $hasMessages = $count > 0 || $hasMessages;
                        $processed += $count;
                        if ($this->loop instanceof SignalLoop && $this->loop->isInterrupted()) {
                            break 2;
                        }

                        $statusReporter->emit(
                            $once ? 'done' : 'working',
                            $once
                                ? sprintf('Queue "%s": %d messages processed', $queueName, $count)
                                : sprintf('Waiting for messages on queue "%s"', $queueName),
                            $activeId,
                        );
                        $activeId = null;

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
                $failure = $exception;
                throw $exception;
            } finally {
                if ($this->loop instanceof ScopedLoopInterface) {
                    try {
                        $this->loop->finish();
                    } catch (Throwable $exception) {
                        // Preserve an original processing error if restoring the loop also fails.
                        if ($failure === null) {
                            throw $exception;
                        }
                    }
                }
            }

            if (!$once || ($this->loop instanceof SignalLoop && $this->loop->isInterrupted())) {
                $statusReporter->clear();
                $statusReporter->emit('idle', 'Queue consumption stopped');
            } else {
                $statusReporter->emit('done', sprintf('%d messages processed', $processed));
            }

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            if ($activeId !== null) {
                $statusReporter->emit('error', 'Queue consumption failed', $activeId);
            }
            $statusReporter->emit('error', 'Consume failed');
            throw $exception;
        }
    }

    /** @param list<string> $queueNames */
    private static function rootMessage(array $queueNames, bool $once): string
    {
        $count = count($queueNames);
        $description = $count === 1 ? sprintf('queue "%s"', $queueNames[0]) : sprintf('%d queues', $count);

        return $once
            ? sprintf('Consuming %s in one pass', $description)
            : sprintf('Polling %s', $description);
    }
}
