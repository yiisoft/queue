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

final class ListenCommand extends Command
{
    protected static $defaultName = 'queue/listen';
    protected static $defaultDescription = 'Listens the queue and executes messages as they come. Needs to be stopped manually.';

    private QueueFactoryInterface $queueFactory;

    public function __construct(QueueFactoryInterface $queueFactory)
    {
        parent::__construct();
        $this->queueFactory = $queueFactory;
    }

    public function configure(): void
    {
        $this->addArgument(
            'channel',
            InputArgument::OPTIONAL,
            'Queue channel name to connect to',
            QueueFactory::DEFAULT_CHANNEL_NAME
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->queueFactory
            ->get($input->getArgument('channel'))
            ->listen();

        return ExitCode::OK;
    }
}
