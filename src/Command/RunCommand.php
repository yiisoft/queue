<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Queue\QueueFactory;
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
            'Queue channel name list to connect to',
            $this->channels,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $channel */
        foreach ($input->getArgument('channel') as $channel) {
            $output->writeln("Processing channel $channel");
            $this->queueFactory
                ->get($channel)
                ->run();
        }

        return ExitCode::OK;
    }
}
