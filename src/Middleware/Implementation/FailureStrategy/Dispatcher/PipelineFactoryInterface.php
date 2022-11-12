<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher;

/**
 * Factory for pipeline creating
 */
interface PipelineFactoryInterface
{
    /**
     * Returns a pipeline for the given channel. Returns a default pipeline if there is no configuration for the given one.
     *
     * @param string $channelName Queue channel for which to get a pipeline
     *
     * @return PipelineInterface
     */
    public function get(string $channelName): PipelineInterface;
}
