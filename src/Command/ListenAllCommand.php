<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\QueueFactoryInterface;

final class ListenAllCommand extends Command
{
    protected static $defaultName = 'queue:listen-all';
    protected static $defaultDescription = 'Listens the all the given queues and executes messages as they come. ' .
    'Meant to be used in development environment only. ' .
    'Listens all configured queues by default in case you\'re using yiisoft/config. ' .
    'Needs to be stopped manually.';

    private QueueFactoryInterface $queueFactory;
    private LoopInterface $loop;

    public function __construct(QueueFactoryInterface $queueFactory, LoopInterface $loop, private array $channels)
    {
        parent::__construct();

        $this->queueFactory = $queueFactory;
        $this->loop = $loop;
    }

    public function configure(): void
    {
        $this->addArgument(
            'channel',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Queue channel name list to connect to',
            $this->channels,
        )
            ->addOption(
                'pause',
                'p',
                InputOption::VALUE_REQUIRED,
                'Pause between queue channel iterations in seconds. May save some CPU. Default: 1',
                1,
            )
            ->addOption(
                'maximum',
                'm',
                InputOption::VALUE_REQUIRED,
                'Maximum number of messages to process in each channel before switching to another channel. ' .
                   'Default is 0 (no limits).',
                0,
            );

        $this->addUsage('[channel1 [channel2 [...]]] [--timeout=<timeout>] [--maximum=<maximum>]');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queues = [];
        /** @var string $channel */
        foreach ($input->getArgument('channel') as $channel) {
            $queues[] = $this->queueFactory->get($channel);
        }

        while ($this->loop->canContinue()) {
            $hasMessages = false;
            foreach ($queues as $queue) {
                $hasMessages = $queue->run((int)$input->getOption('maximum')) > 0 || $hasMessages;
            }

            if (!$hasMessages) {
                sleep((int)$input->getOption('pause'));
            }
        }

        return ExitCode::OK;
    }
}
