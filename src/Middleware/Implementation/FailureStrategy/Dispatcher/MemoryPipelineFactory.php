<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher;

/**
 * {@inheritDoc}
 *
 * This factory caches created pipelines into memory
 */
final class MemoryPipelineFactory extends PipelineFactory
{
    /**
     * @var PipelineInterface[]
     */
    private array $built = [];

    public function get(string $channelName): PipelineInterface
    {
        if (!isset($this->pipelines[$channelName]) || $this->pipelines[$channelName] === []) {
            $channelName = self::DEFAULT_PIPELINE;
        }

        $this->built[$channelName] = $this->built[$channelName] ?? $this->create($this->pipelines[$channelName]);

        return $this->built[$channelName];
    }
}
