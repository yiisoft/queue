<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Queue\Queue;

class RunCommand extends Command
{
    protected static $defaultName = 'queue/run';
    private Queue $queue;

    public function __construct(?string $name, Queue $queue)
    {
        parent::__construct($name);
        $this->queue = $queue;

        $this->setDescription('Runs all the existing messages in the queue. Exits once messages are over.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->queue->run();

        return 0;
    }
}
