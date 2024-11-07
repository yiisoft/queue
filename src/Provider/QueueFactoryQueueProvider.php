<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Factory\StrictFactory;
use Yiisoft\Queue\QueueInterface;

use function array_key_exists;
use function sprintf;

/**
 * Queue provider based on queue definitions.
 *
 * @see https://github.com/yiisoft/definitions/
 * @see https://github.com/yiisoft/factory/
 */
final class QueueFactoryQueueProvider implements QueueProviderInterface
{
    /**
     * @psalm-var array<string, QueueInterface|null>
     */
    private array $queues = [];

    private readonly StrictFactory $factory;

    /**
     * @param array $definitions Definitions to create queues with indexed by channel names.
     * @param ContainerInterface|null $container Container to use for resolving dependencies.
     * @param bool $validate If definitions should be validated when set.
     *
     * @psalm-param array<string, mixed> $definitions
     * @throws InvalidQueueConfigException
     */
    public function __construct(
        array $definitions,
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

    /**
     * @throws InvalidQueueConfigException
     */
    private function getOrTryCreate(string $channel): QueueInterface|null
    {
        if (array_key_exists($channel, $this->queues)) {
            return $this->queues[$channel];
        }

        if ($this->factory->has($channel)) {
            $queue = $this->factory->create($channel);
            if (!$queue instanceof QueueInterface) {
                throw new InvalidQueueConfigException(
                    sprintf(
                        'Queue must implement "%s". For channel "%s" got "%s" instead.',
                        QueueInterface::class,
                        $channel,
                        get_debug_type($queue),
                    ),
                );
            }
            $this->queues[$channel] = $queue->withChannelName($channel);
        } else {
            $this->queues[$channel] = null;
        }

        return $this->queues[$channel];
    }
}
