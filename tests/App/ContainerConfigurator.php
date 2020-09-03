<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Di\AbstractContainerConfigurator;
use Yiisoft\Di\Container;

class ContainerConfigurator extends AbstractContainerConfigurator
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function set(string $id, $definition): void
    {
        $this->container->set($id, $definition);
    }
}
