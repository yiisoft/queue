<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests\Serializers;

use Yiisoft\Yii\Queue\Serializers\IgbinarySerializer;

/**
 * Igbinary Serializer Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class IgbinarySerializerTest extends TestCase
{
    protected function createSerializer()
    {
        return new IgbinarySerializer();
    }

    protected function setUp()
    {
        if (!extension_loaded('igbinary')) {
            $this->markTestSkipped('Igbinary extension is not loaded.');
        }
        parent::setUp();
    }
}
