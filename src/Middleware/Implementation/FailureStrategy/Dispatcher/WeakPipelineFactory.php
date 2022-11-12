<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher;

use WeakReference;

/**
 * {@inheritDoc}
 *
 * This factory uses {@see WeakReference} to cache created pipelines
 */
final class WeakPipelineFactory extends PipelineFactory
{
    /**
     * @var WeakReference<PipelineInterface>[]
     */
    private array $built = [];

    public function get(string $channelName): PipelineInterface
    {
        if (!isset($this->pipelines[$channelName]) || $this->pipelines[$channelName] === []) {
            $channelName = self::DEFAULT_PIPELINE;
        }

        if (isset($this->built[$channelName]) && $result = $this->built[$channelName]->get()) {
            return $result;
        }

        $result = $this->create($this->pipelines[$channelName]);
        $this->built[$channelName] = WeakReference::create($result);

        return $result;
    }
}
