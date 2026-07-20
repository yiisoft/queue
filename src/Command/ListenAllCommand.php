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
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueConsumerInterface;

use function get_debug_type;
use function sprintf;

#[AsCommand(
    'queue:listen-all',
    'Listens the all the given queues and executes messages as they come. '
        . 'Meant to be used in development environment only. '
        . 'Listens all consumer-capable configured queues by default. '
        . 'Needs to be stopped manually.',
)]
final class ListenAllCommand extends Command
{
    public function __construct(
        private readonly QueueProviderInterface $queueProvider,
        private readonly LoopInterface $loop,
    ) {
        parent::__construct();
    }

    /**
     * @codeCoverageIgnore
     */
    public function configure(): void
    {
        $this->addArgument(
            'queue',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Queue name list to connect to',
            [],
        )
            ->addOption(
                'pause',
                'p',
                InputOption::VALUE_REQUIRED,
                'Pause between queue iterations in seconds. May save some CPU. Default: 1',
                1,
            )
            ->addOption(
                'limit',
                'm',
                InputOption::VALUE_REQUIRED,
                'Maximum number of messages to process in each queue before switching to another queue. '
                    . 'Default is 0 (no limits).',
                0,
            );

        $this->addUsage('[queue1 [queue2 [...]]] [--pause=<pause>] [--limit=<limit>]');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string[] $queueNames */
        $queueNames = $input->getArgument('queue');
        $queueIsRequired = $queueNames !== [];
        if (!$queueIsRequired) {
            $queueNames = $this->queueProvider->getNames();
        }

        $queues = [];
        /** @var string $queue */
        foreach ($queueNames as $queue) {
            $queueConsumer = $this->getQueueConsumer($queue, $queueIsRequired);
            if ($queueConsumer !== null) {
                $queues[] = $queueConsumer;
            }
        }

        if ($queues === []) {
            return Command::SUCCESS;
        }

        $pauseSeconds = (int) $input->getOption('pause');
        if ($pauseSeconds < 0) {
            $pauseSeconds = 1;
        }

        while ($this->loop->canContinue()) {
            $hasMessages = false;
            foreach ($queues as $queue) {
                $hasMessages = $queue->run((int) $input->getOption('limit')) > 0 || $hasMessages;
            }

            if (!$hasMessages) {
                /** @psalm-var 0|positive-int $pauseSeconds */
                sleep($pauseSeconds);
            }
        }

        return 0;
    }

    private function getQueueConsumer(string $name, bool $required): ?QueueConsumerInterface
    {
        $queue = $this->queueProvider->get($name);

        if (!$queue instanceof QueueConsumerInterface) {
            if (!$required) {
                return null;
            }

            throw new InvalidQueueConfigException(
                sprintf(
                    'Queue "%s" must implement "%s" to consume messages. Got "%s" instead.',
                    $name,
                    QueueConsumerInterface::class,
                    get_debug_type($queue),
                ),
            );
        }

        return $queue;
    }
}
