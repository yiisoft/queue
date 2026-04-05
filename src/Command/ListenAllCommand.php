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
use Yiisoft\Queue\Provider\QueueProviderInterface;

#[AsCommand(
    'queue:listen-all',
    'Listens the all the given queues and executes messages as they come. '
        . 'Meant to be used in development environment only. '
        . 'Listens all configured queues by default in case you\'re using yiisoft/config. '
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
            $this->queueProvider->getNames(),
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
        $queues = [];
        /** @var string $queue */
        foreach ($input->getArgument('queue') as $queue) {
            $queues[] = $this->queueProvider->get($queue);
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
}
