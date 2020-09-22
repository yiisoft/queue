<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy;

use Psr\Container\ContainerInterface;
use WeakReference;

class DispatcherFactory
{
    public const DEFAULT_PIPELINE = 'failure-pipeline-default';

    private array $pipelines;
    private array $built = [];
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    public function __construct(array $pipelines, ContainerInterface $container)
    {
        $this->pipelines = $pipelines;
        $this->container = $container;
    }

    public function get(string $payloadName)
    {
        $name = isset($this->pipelines[$payloadName]) ? $payloadName : self::DEFAULT_PIPELINE;
        if (isset($this->built[$name]) && $result = $this->built[$name]->get()) {
            return $result;
        }

        $result = $this->create($this->pipelines[$name]);
        $this->built[$name] = WeakReference::create($result);

        return $result;
    }

    private function create($name)
    {
        if ($this->pipelines[$name] instanceof Abcde) {
            return $this->pipelines[$name];
        }

        return $this->container->get($this->pipelines[$name]);
    }
}
