<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Queue\Queue;

class ListenCommand extends Command
{
    protected static $defaultName = 'queue/listen';
    /**
     * @var Queue
     */
    private Queue $queue;

    public function __construct(?string $name, Queue $queue)
    {
        parent::__construct($name);
        $this->queue = $queue;
        $this->setDescription('Listens the queue and executes messages as they come. Needs to be stopped manually.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->queue->listen();

        return 0;
    }
}
