<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use InvalidArgumentException;
use WeakReference;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Factory\Factory;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\ChannelIncorrectlyConfigured;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\ChannelNotConfiguredException;

final class QueueFactory implements QueueFactoryInterface
{
    private bool $enableRuntimeChannelDefinition;
    private ?AdapterInterface $defaultAdapter;
    private array $queueCollection = [];

    /**
     * QueueFactory constructor.
     *
     * @param array<string, mixed> $channelConfiguration Configuration array in [channel_name => definition] format.
     * "Definition" here is a {@see Factory} definition
     * @param QueueInterface $queue A default queue implementation. `$queue->withAdapter()` will be returned
     * with the `get` method
     * @param bool $enableRuntimeChannelDefinition A flag whether to enable a such behavior when there is no
     * explicit channel adapter definition: `return $this->queue->withAdapter($this->adapter->withChannel($channel)`
     * When this flag is set to false, only explicit definitions from the $definition parameter are used.
     * @param AdapterInterface|null $defaultAdapter A default adapter implementation.
     * It must be set when $enableRuntimeChannelDefinition is true.
     */
    public function __construct(
        private array $channelConfiguration,
        private QueueInterface $queue,
        private Factory $yiiFactory,
        bool $enableRuntimeChannelDefinition = false,
        ?AdapterInterface $defaultAdapter = null
    ) {
        if ($enableRuntimeChannelDefinition === true && $defaultAdapter === null) {
            $message = 'Either $enableRuntimeChannelDefinition must be false, or $defaultAdapter should be provided.';

            throw new InvalidArgumentException($message);
        }
        $this->enableRuntimeChannelDefinition = $enableRuntimeChannelDefinition;
        $this->defaultAdapter = $defaultAdapter;
    }

    public function get(string $channel = self::DEFAULT_CHANNEL_NAME): QueueInterface
    {
        if ($channel === $this->queue->getChannelName()) {
            return $this->queue;
        }

        if (isset($this->queueCollection[$channel]) && $this->queueCollection[$channel]->get() !== null) {
            $queue = $this->queueCollection[$channel]->get();
        } else {
            $queue = $this->create($channel);
            $this->queueCollection[$channel] = WeakReference::create($queue);
        }

        return $queue;
    }

    /**
     * @throws InvalidConfigException
     */
    private function create(string $channel): QueueInterface
    {
        if (isset($this->channelConfiguration[$channel])) {
            $adapter = $this->yiiFactory->create($this->channelConfiguration[$channel]);
            if (!$adapter instanceof AdapterInterface) {
                throw new ChannelIncorrectlyConfigured($channel, $adapter);
            }

            return $this->queue->withAdapter($adapter);
        }

        if ($this->enableRuntimeChannelDefinition === false) {
            throw new ChannelNotConfiguredException($channel);
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->queue->withAdapter($this->defaultAdapter->withChannel($channel));
    }
}
