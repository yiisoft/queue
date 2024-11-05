<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Factory\StrictFactory;
use Yiisoft\Queue\QueueInterface;

use function array_key_exists;

final class FactoryQueueProvider implements QueueProviderInterface
{
    /**
     * @psalm-var array<string, QueueInterface|null>
     */
    private array $queues = [];

    private readonly StrictFactory $factory;

    /**
     * @throws InvalidQueueConfigException
     */
    public function __construct(
        array $definitions = [],
        ?ContainerInterface $container = null,
        bool $validate = true,
    ) {
        try {
            $this->factory = new StrictFactory($definitions, $container, $validate);
        } catch (InvalidConfigException $exception) {
            throw new InvalidQueueConfigException($exception->getMessage(), previous: $exception);
        }
    }

    public function get(string $channel): QueueInterface
    {
        $queue = $this->getOrTryCreate($channel);
        if ($queue === null) {
            throw new ChannelNotFoundException($channel);
        }
        return $queue;
    }

    public function has(string $channel): bool
    {
        return $this->factory->has($channel);
    }

    private function getOrTryCreate(string $channel): QueueInterface|null
    {
        if (array_key_exists($channel, $this->queues)) {
            return $this->queues[$channel];
        }

        if ($this->factory->has($channel)) {
            $this->queues[$channel] = $this->factory->create($channel);
        } else {
            $this->queues[$channel] = null;
        }

        return $this->queues[$channel];
    }
}
