<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher;

use Throwable;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\FailureStrategyInterface;

use function array_reverse;

abstract class PipelineFactory implements PipelineFactoryInterface
{
    public const DEFAULT_PIPELINE = 'failure-pipeline-default';

    public function __construct(protected array $pipelines, private FailureStrategyFactory $factory)
    {
        if (!isset($this->pipelines[self::DEFAULT_PIPELINE])) {
            $this->pipelines[self::DEFAULT_PIPELINE] = [];
        }
    }

    protected function create(PipelineInterface|array $definition): PipelineInterface
    {
        if ($definition instanceof PipelineInterface) {
            return $definition;
        }

        $handler = $this->getLastPipeline();
        foreach (array_reverse($definition) as $strategy) {
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
