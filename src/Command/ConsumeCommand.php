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
use Yiisoft\Queue\DefaultQueue;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;

use function array_unique;
use function count;
use function crc32;
use function intdiv;
use function preg_replace;
use function sprintf;
use function str_pad;
use function substr;
use function trim;
use function strlen;

use const STR_PAD_LEFT;

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

            try {
                $consumer = $this->queueProvider->getConsumer($queueName);
            } catch (Throwable $exception) {
                $statusReporter->emit('error', sprintf('Consume on queue "%s" failed', $queueName));

                throw $exception;
            }

            if ($consumer->isSynchronous()) {
                $output->writeln('Synchronous mode: no adapter is configured, messages are processed on push.');
                $statusReporter->emit(
                    'done',
                    'Synchronous mode: messages are processed on push',
                    self::statusId($queueName),
                );

                return Command::SUCCESS;
            }

            $statusId = self::statusId($queueName);
            $statusReporter->emit('working', sprintf('Listening on queue "%s"', $queueName), $statusId);

            try {
                $consumer->listen();
            } catch (Throwable $exception) {
                if ($statusId !== null) {
                    $statusReporter->emit('error', sprintf('Queue "%s" failed', $queueName), $statusId);
                }

                $statusReporter->emit('error', sprintf('Consume on queue "%s" failed', $queueName));

                throw $exception;
            }

            $statusReporter->emit('done', sprintf('Stopped listening on queue "%s"', $queueName), $statusId);

            return Command::SUCCESS;
        }

        if ($queueNames === []) {
            $queueNames = $this->queueProvider->getConsumerQueueNames();
        }

        $statusReporter->emit('working', self::rootMessage($queueNames, $once));

        $consumers = [];
        try {
            foreach ($queueNames as $queueName) {
                $consumers[$queueName] = $this->queueProvider->getConsumer($queueName);
            }
        } catch (Throwable $exception) {
            $statusReporter->emit('error', 'Resolving consumers failed');

            throw $exception;
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
        $activeName = null;
        $activeId = null;
        $exception = null;

        try {
            if ($this->loop instanceof ScopedLoopInterface) {
                $this->loop->start();
            }

            while ($once || $this->loop->canContinue()) {
                $hasMessages = false;
                foreach ($queueNames as $queueName) {
                    if ($once) {
                        $output->write("Processing queue $queueName... ");
                    }

                    $activeName = $queueName;
                    $activeId = self::statusId($queueName);
                    $statusReporter->emit('working', sprintf('Processing queue "%s"', $queueName), $activeId);

                    $count = $consumers[$queueName]->run($limit);
                    $hasMessages = $count > 0 || $hasMessages;
                    $processed += $count;

                    $statusReporter->emit('done', sprintf('Queue "%s": %d messages processed', $queueName, $count), $activeId);

                    if ($once) {
                        $output->writeln("Messages processed: $count.");
                    }
                }

                if ($once) {
                    break;
                }

                if (!$hasMessages && $this->loop->canContinue()) {
                    if ($activeName !== null && $activeId !== null) {
                        $statusReporter->emit('idle', sprintf('Queue "%s" is idle', $activeName), $activeId);
                    }

                    /** @psalm-var 0|positive-int $pauseSeconds */
                    sleep($pauseSeconds);
                }
            }
        } catch (Throwable $caught) {
            $exception = $caught;
        }

        try {
            if ($this->loop instanceof ScopedLoopInterface) {
                $this->loop->finish();
            }
        } catch (Throwable $caught) {
            $exception ??= $caught;
        }

        if ($exception !== null) {
            if ($activeName !== null && $activeId !== null) {
                $statusReporter->emit('error', sprintf('Queue "%s" failed', $activeName), $activeId);
            }

            $statusReporter->emit('error', 'Consume failed');

            throw $exception;
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
        $count = count(array_unique($queueNames));
        if ($count === 0) {
            $description = 'all configured queues';
        } elseif ($count === 1) {
            $description = sprintf('queue "%s"', $queueNames[0]);
        } else {
            $description = sprintf('%d queues', $count);
        }

        return $once
            ? sprintf('Consuming %s in one pass', $description)
            : sprintf('Listening on %s', $description);
    }

    /**
     * A task id for the queue in terminal status reports.
     *
     * Characters outside the protocol id alphabet, including "/", are replaced with "-", so the result always
     * holds a single segment. When sanitizing or truncation changes the name, a short digest of the original name
     * is appended so that distinct queues keep distinct records. Returns `null` when the queue name cannot be
     * represented at all, in which case the report falls back to the program-level record.
     */
    private static function statusId(string $queueName): ?string
    {
        $segment = trim((string) preg_replace('/[^A-Za-z0-9_.+-]+/', '-', $queueName), '-');

        if ($segment === '') {
            return null;
        }

        if ($segment !== $queueName || strlen($segment) > 32) {
            $segment = substr($segment, 0, 23) . '-' . self::idSuffix($queueName);
        }

        return 'queue/' . $segment;
    }

    /**
     * A 7-character base36 digest appended to a sanitized or truncated queue name segment.
     */
    private static function idSuffix(string $queueName): string
    {
        $alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';
        $value = crc32($queueName);
        $digest = '';
        do {
            $digest = $alphabet[$value % 36] . $digest;
            $value = intdiv($value, 36);
        } while ($value > 0);

        return str_pad($digest, 7, '0', STR_PAD_LEFT);
    }
}
