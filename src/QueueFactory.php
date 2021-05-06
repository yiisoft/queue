<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use WeakReference;
use Yiisoft\Factory\Exception\InvalidConfigException;
use Yiisoft\Factory\Factory;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Exception\DriverConfiguration\ChannelIncorrectlyConfigured;
use Yiisoft\Yii\Queue\Exception\DriverConfiguration\ChannelNotConfiguredException;
use Yiisoft\Yii\Queue\Exception\DriverConfiguration\EmptyDefaultDriverException;

final class QueueFactory implements QueueFactoryInterface
{
    private array $channelConfiguration;
    private Queue $queue;
    private bool $enableRuntimeChannelDefinition;
    private ?DriverInterface $defaultDriver;
    private Factory $yiiFactory;
    private array $queueCollection = [];

    public function __construct(
        array $channelConfiguration,
        Queue $queue,
        Factory $yiiFactory,
        bool $enableRuntimeChannelDefinition = false,
        ?DriverInterface $defaultDriver = null
    ) {
        $this->channelConfiguration = $channelConfiguration;
        $this->queue = $queue;
        $this->yiiFactory = $yiiFactory;
        $this->enableRuntimeChannelDefinition = $enableRuntimeChannelDefinition;
        $this->defaultDriver = $defaultDriver;
    }

    /**
     * @throws InvalidConfigException
     */
    public function get(?string $channel = null): Queue
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
     * @return Queue
     * @throws InvalidConfigException
     */
    private function create(string $channel): Queue
    {
        if (isset($this->channelConfiguration[$channel])) {
            $queue = $this->yiiFactory->create($this->channelConfiguration[$channel]);
            if (!$queue instanceof Queue) {
                throw new ChannelIncorrectlyConfigured($channel, $queue);
            }

            return $queue;
        }

        if ($this->enableRuntimeChannelDefinition === false) {
            throw new ChannelNotConfiguredException($channel);
        } elseif ($this->defaultDriver === null) {
            throw new EmptyDefaultDriverException();
        } else {
            return $this->queue->withDriver($this->defaultDriver->withChannel($channel));
        }
    }
}
