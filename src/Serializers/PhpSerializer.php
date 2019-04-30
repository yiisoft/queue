<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Serializers;

use yii\base\BaseObject;

/**
 * Php Serializer.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class PhpSerializer extends BaseObject implements SerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize($job): string
    {
        return serialize($job);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        return unserialize($serialized);
    }
}
