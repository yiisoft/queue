<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Exception\DriverConfiguration\ChannelNotConfigureException;
use Yiisoft\Yii\Queue\Exception\DriverConfiguration\EmptyDefaultDriverException;

final class QueueFactory implements QueueFactoryInterface
{
    private array $channelConfiguration;
    private Queue $queue;
    private bool $enableRuntimeChannelDefinition;
    private ?DriverInterface $defaultDriver;

    public function __construct(
        array $channelConfiguration,
        Queue $queue,
        bool $enableRuntimeChannelDefinition = false,
        ?DriverInterface $defaultDriver = null
    ) {
        $this->channelConfiguration = $channelConfiguration;
        $this->queue = $queue;
        $this->enableRuntimeChannelDefinition = $enableRuntimeChannelDefinition;
        $this->defaultDriver = $defaultDriver;
    }

    public function get(?string $channel = null): Queue
    {
        if ($channel === null) {
            return $this->queue;
        }

        if (!isset($this->channelConfiguration[$channel])) {
            if ($this->enableRuntimeChannelDefinition === false) {
                throw new ChannelNotConfigureException($channel);
            } elseif ($this->defaultDriver === null) {
                throw new EmptyDefaultDriverException();
            } else {
                return $this->queue->withDriver($this->defaultDriver->withChannel($channel));
            }
        }

        // TODO
    }
}
