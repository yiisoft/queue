<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher;

use Throwable;
use WeakReference;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\FailureStrategyInterface;

final class DispatcherFactory implements DispatcherFactoryInterface
{
    public const DEFAULT_PIPELINE = 'failure-pipeline-default';
    /**
     * @var WeakReference<DispatcherInterface>[]
     */
    private array $built = [];

    public function __construct(private array $pipelines, private FailureStrategyFactory $factory)
    {
        if (!isset($this->pipelines[self::DEFAULT_PIPELINE])) {
            $this->pipelines[self::DEFAULT_PIPELINE] = [];
        }
    }

    public function get(string $channelName): DispatcherInterface
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

    private function create(PipelineInterface|array $definition): DispatcherInterface
    {
        if ($definition instanceof PipelineInterface) {
            return new Dispatcher($definition);
        }

        return new Dispatcher($this->createPipeline($definition));
    }

    private function createPipeline(array $pipeline): PipelineInterface
    {
        $handler = $this->getLastPipeline();
        foreach (array_reverse($pipeline) as $strategy) {
            $handler = $this->wrap($this->factory->create($strategy), $handler);
        }

        return $handler;
    }

    private function wrap(FailureStrategyInterface $strategy, PipelineInterface $pipeline): PipelineInterface
    {
        return new class ($strategy, $pipeline) implements PipelineInterface {
            private FailureStrategyInterface $strategy;
            private PipelineInterface $pipeline;

            public function __construct(FailureStrategyInterface $strategy, PipelineInterface $pipeline)
            {
                $this->strategy = $strategy;
                $this->pipeline = $pipeline;
            }

            public function handle(ConsumeRequest $request, Throwable $exception): ConsumeRequest
            {
                return $this->strategy->handle($request, $exception, $this->pipeline);
            }
        };
    }

    private function getLastPipeline(): PipelineInterface
    {
        return new class () implements PipelineInterface {
            public function handle(ConsumeRequest $request, Throwable $exception): ConsumeRequest
            {
                throw $exception;
            }
        };
    }
}
