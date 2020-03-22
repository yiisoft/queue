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

/**
 * Base Test Case.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends BaseTestCase
{
    public $container;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        Builder::rebuild();
        $this->container = new Container(require Builder::path('tests-app'));
    }
}
