<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Factory\StrictFactory;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\ChannelNormalizer;
use Yiisoft\Queue\QueueInterface;

use function array_key_exists;
use function sprintf;

/**
 * This queue provider create new queue objects based on adapter definitions.
 *
 * @see https://github.com/yiisoft/definitions/
 * @see https://github.com/yiisoft/factory/
 */
final class AdapterFactoryQueueProvider implements QueueProviderInterface
{
    /**
     * @psalm-var array<string, QueueInterface|null>
     */
    private array $queues = [];

    private readonly StrictFactory $factory;

    /**
     * @param QueueInterface $baseQueue Base queue for queues creation.
     * @param array $definitions Adapter definitions indexed by channel names.
     * @param ContainerInterface|null $container Container to use for dependencies resolving.
     * @param bool $validate If definitions should be validated when set.
     *
     * @psalm-param array<string, mixed> $definitions
     * @throws InvalidQueueConfigException
     */
    public function __construct(
        private readonly QueueInterface $baseQueue,
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

    public function get(string|BackedEnum $channel): QueueInterface
    {
        $channel = ChannelNormalizer::normalize($channel);

        $queue = $this->getOrTryToCreate($channel);
        if ($queue === null) {
            throw new ChannelNotFoundException($channel);
        }

        return $queue;
    }

    public function has(string|BackedEnum $channel): bool
    {
        $channel = ChannelNormalizer::normalize($channel);
        return $this->factory->has($channel);
    }

    /**
     * @throws InvalidQueueConfigException
     */
    private function getOrTryToCreate(string $channel): QueueInterface|null
    {
        if (array_key_exists($channel, $this->queues)) {
            return $this->queues[$channel];
        }

        if ($this->factory->has($channel)) {
            $adapter = $this->factory->create($channel);
            if (!$adapter instanceof AdapterInterface) {
                throw new InvalidQueueConfigException(
                    sprintf(
                        'Adapter must implement "%s". For channel "%s" got "%s" instead.',
                        AdapterInterface::class,
                        $channel,
                        get_debug_type($adapter),
                    ),
                );
            }
            $this->queues[$channel] = $this->baseQueue->withAdapter($adapter->withChannel($channel));
        } else {
            $this->queues[$channel] = null;
        }

        return $this->queues[$channel];
    }
}
