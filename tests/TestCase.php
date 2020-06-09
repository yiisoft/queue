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
use Yiisoft\Yii\Console\Config\EventConfigurator;

/**
 * Base Test Case.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends BaseTestCase
{
    public Container $container;

    protected function setUp(): void
    {
        $this->container = new Container(require Builder::path('tests-app'));
        $eventConfigurator = $this->container->get(EventConfigurator::class);
        $eventConfigurator->registerListeners(require Builder::path('events-console'));
    }
}
