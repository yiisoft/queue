<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Queue\QueueFactory;

class ListenCommand extends Command
{
    protected static $defaultName = 'queue/listen';
    private QueueFactory $queueFactory;

    public function __construct(?string $name, QueueFactory $queueFactory)
    {
        parent::__construct($name);
        $this->queueFactory = $queueFactory;
        $this->setDescription('Listens the queue and executes messages as they come. Needs to be stopped manually.');

        $this->addArgument('channel', InputArgument::OPTIONAL, 'Queue channel name to connect to', QueueFactory::DEFAULT_CHANNEL_NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->queueFactory->get($input->getArgument('channel'))->listen();

        return 0;
    }
}
