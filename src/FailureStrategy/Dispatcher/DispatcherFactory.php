<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy\Dispatcher;

use Psr\Container\ContainerInterface;
use WeakReference;
use Yiisoft\Yii\Queue\FailureStrategy\Strategy\FailureStrategyInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;

final class DispatcherFactory implements DispatcherFactoryInterface
{
    public const DEFAULT_PIPELINE = 'failure-pipeline-default';

    private array $pipelines;
    /**
     * @var WeakReference[]
     */
    private array $built = [];
    private ContainerInterface $container;

    public function __construct(array $pipelines, ContainerInterface $container)
    {
        $this->pipelines = $pipelines;
        $this->container = $container;

        if (!isset($this->pipelines[self::DEFAULT_PIPELINE])) {
            $this->pipelines[self::DEFAULT_PIPELINE] = [$this->getEmptyStrategy()];
        }
    }

    public function get(string $payloadName): DispatcherInterface
    {
        if (!isset($this->pipelines[$payloadName]) || isset($this->pipelines[$payloadName]) === []) {
            $payloadName = self::DEFAULT_PIPELINE;
        }
        /** @var DispatcherInterface $result */
        if (isset($this->built[$payloadName]) && $result = $this->built[$payloadName]->get()) {
            return $result;
        }

        $result = $this->create($this->pipelines[$payloadName]);
        $this->built[$payloadName] = WeakReference::create($result);

        return $result;
    }

    private function create($definition): DispatcherInterface
    {
        if ($definition instanceof PipelineInterface) {
            return new Dispatcher($definition);
        }

        return new Dispatcher($this->createPipeline($definition));
    }

    private function createPipeline(array $pipeline): PipelineInterface
    {
        $handler = null;
        foreach (array_reverse($pipeline) as $strategy) {
            $strategy = $strategy instanceof FailureStrategyInterface ? $strategy : $this->container->get($strategy);

            $handler = $this->wrap($strategy, $handler);
        }

        return $handler;
    }

    private function wrap(FailureStrategyInterface $strategy, ?PipelineInterface $pipeline): PipelineInterface
    {
        return new class ($strategy, $pipeline) implements PipelineInterface {
            private FailureStrategyInterface $strategy;
            private ?PipelineInterface $pipeline;

            public function __construct(FailureStrategyInterface $strategy, ?PipelineInterface $pipeline)
            {
                $this->strategy = $strategy;
                $this->pipeline = $pipeline;
            }

            public function handle(MessageInterface $message): bool
            {
                return $this->strategy->handle($message, $this->pipeline);
            }
        };
    }

    private function getEmptyStrategy(): FailureStrategyInterface
    {
        return new class () implements FailureStrategyInterface {
            public function handle(MessageInterface $message, ?PipelineInterface $pipeline): bool
            {
                return false;
            }
        };
    }
}
