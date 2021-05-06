<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use WeakReference;
use Yiisoft\Factory\Exception\InvalidConfigException;
use Yiisoft\Factory\Factory;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\ChannelIncorrectlyConfigured;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\ChannelNotConfiguredException;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\EmptyDefaultAdapterException;

final class QueueFactory
{
    private array $channelConfiguration;
    private QueueInterface $queue;
    private bool $enableRuntimeChannelDefinition;
    private ?AdapterInterface $defaultAdapter;
    private Factory $yiiFactory;
    private array $queueCollection = [];

    /**
     * QueueFactory constructor.
     *
     * @param array<string, mixed> $channelConfiguration Configuration array in [channel_name => definition] format.
     *        "Definition" here is a {@see Factory} definition
     * @param QueueInterface $queue A default queue implementation. `$queue->withAdapter()` will be returned
     *        with the `get` method
     * @param Factory $yiiFactory
     * @param bool $enableRuntimeChannelDefinition A flag whether to enable a such behavior when there is no
     *        explicit channel adapter definition: `return $this->queue->withAdapter($this->adapter->withChannel($channel)`
     *        When this flag is set to false, only explicit definitions from the $definition parameter are used.
     * @param AdapterInterface|null $defaultAdapter A default adapter implementation.
     *        It must be set when $enableRuntimeChannelDefinition is true.
     */
    public function __construct(
        array $channelConfiguration,
        QueueInterface $queue,
        Factory $yiiFactory,
        bool $enableRuntimeChannelDefinition = false,
        ?AdapterInterface $defaultAdapter = null
    ) {
        $this->channelConfiguration = $channelConfiguration;
        $this->queue = $queue;
        $this->yiiFactory = $yiiFactory;
        $this->enableRuntimeChannelDefinition = $enableRuntimeChannelDefinition;
        $this->defaultAdapter = $defaultAdapter;
    }

    /**
     * @throws InvalidConfigException
     */
    public function get(?string $channel = null): QueueInterface
    {
        if ($channel === null) {
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
     * @param string $channel
     *
     * @throws InvalidConfigException
     *
     * @return QueueInterface
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
        if ($this->defaultAdapter === null) {
            throw new EmptyDefaultAdapterException();
        }
        return $this->queue->withAdapter($this->defaultAdapter->withChannel($channel));
    }
}
