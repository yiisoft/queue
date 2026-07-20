<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueConsumerInterface;

use function get_debug_type;
use function sprintf;

#[AsCommand(
    'queue:listen',
    'Listens the queue and executes messages as they come. Needs to be stopped manually.',
)]
final class ListenCommand extends Command
{
    public function __construct(
        private readonly QueueProviderInterface $queueProvider,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'queue',
            InputArgument::OPTIONAL,
            'Queue name to connect to',
            QueueProviderInterface::DEFAULT_QUEUE,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = (string) $input->getArgument('queue');

        $this->getQueueConsumer($queueName)->listen();

        return Command::SUCCESS;
    }

    private function getQueueConsumer(string $name): QueueConsumerInterface
    {
        $queue = $this->queueProvider->get($name);

        if (!$queue instanceof QueueConsumerInterface) {
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
