<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Queue\QueueFactoryInterface;

final class RunCommand extends Command
{
    protected static $defaultName = 'queue/run';
    protected static $defaultDescription = 'Runs all the existing messages in the given queues. ' .
        'Exits once messages are over.';

    private QueueFactoryInterface $queueFactory;
    private array $channels;

    public function __construct(QueueFactoryInterface $queueFactory, array $channels)
    {
        parent::__construct();

        $this->queueFactory = $queueFactory;
        $this->channels = $channels;
    }

    public function configure(): void
    {
        $this->addArgument(
            'channel',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Queue channel name list to connect to.',
            $this->channels,
        )
            ->addOption(
                'maximum',
                'm',
                InputOption::VALUE_REQUIRED,
                'Maximum number of messages to process in each channel. Default is 0 (no limits).',
                0,
            )
            ->addUsage('[channel1 [channel2 [...]]] --maximum 100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $channel */
        foreach ($input->getArgument('channel') as $channel) {
            $output->write("Processing channel $channel... ");
            $count = $this->queueFactory
                ->get($channel)
                ->run((int)$input->getOption('maximum'));

            $output->writeln("Messages processed: $count.");
        }

        return ExitCode::OK;
    }
}
