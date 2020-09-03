<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests;

use Yiisoft\Composer\Config\Builder;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Yiisoft\Di\Container;
use Yiisoft\Yii\Event\EventDispatcherProvider;
use Yiisoft\Yii\Queue\Tests\App\ContainerConfigurator;

/**
 * Base Test Case.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends BaseTestCase
{
    public Container $container;
    protected ContainerConfigurator $containerConfigurator;

    protected function setUp(): void
    {
        $this->container = new Container(require Builder::path('tests-app'));
        $this->containerConfigurator = new ContainerConfigurator($this->container);
        $eventConfigurator = $this->container->get(EventDispatcherProvider::class);
        $eventConfigurator->register($this->container);
    }
}
