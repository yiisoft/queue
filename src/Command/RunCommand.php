<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueConsumerInterface;

use function get_debug_type;
use function sprintf;

#[AsCommand(
    'queue:run',
    'Runs all the existing messages in the given queues. Exits once messages are over.',
)]
final class RunCommand extends Command
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
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Queue name list to connect to.',
            [],
        )
            ->addOption(
                'limit',
                'm',
                InputOption::VALUE_REQUIRED,
                'Maximum number of messages to process in each queue. Default is 0 (no limits).',
                0,
            )
            ->addUsage('[queue1 [queue2 [...]]] --limit 100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string[] $queueNames */
        $queueNames = $input->getArgument('queue');
        $queueIsRequired = $queueNames !== [];
        if (!$queueIsRequired) {
            $queueNames = $this->queueProvider->getNames();
        }

        /** @var string $queue */
        foreach ($queueNames as $queue) {
            $queueConsumer = $this->getQueueConsumer($queue, $queueIsRequired);
            if ($queueConsumer === null) {
                continue;
            }

            $output->write("Processing queue $queue... ");
            $count = $queueConsumer->run((int) $input->getOption('limit'));

            $output->writeln("Messages processed: $count.");
        }

        return 0;
    }

    private function getQueueConsumer(string $name, bool $required): ?QueueConsumerInterface
    {
        $queue = $this->queueProvider->get($name);

        if (!$queue instanceof QueueConsumerInterface) {
            if (!$required) {
                return null;
            }

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
