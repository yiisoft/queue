<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Queue\QueueFactory;
use Yiisoft\Yii\Queue\QueueFactoryInterface;

final class RunCommand extends Command
{
    protected static $defaultName = 'queue:run';
    protected static $defaultDescription = 'Runs all the existing messages in the queue. Exits once messages are over.';

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
            ->run();

        return 0;
    }
}
