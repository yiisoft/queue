<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests\Serializers;

use Yiisoft\Yii\Queue\Serializers\JsonSerializer;

/**
 * Json Serializer Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class JsonSerializerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createSerializer()
    {
        return new JsonSerializer();
    }

    /**
     * @expectedException \yii\exceptions\InvalidConfigException
     */
    public function testInvalidArrayKey()
    {
        $this->createSerializer()->serialize([
            'class' => 'failed param',
        ]);
    }
}
