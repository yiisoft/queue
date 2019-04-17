<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\tests\app;

use yii\base\BaseObject;
use yii\helpers\Yii;
use yii\queue\JobInterface;

/**
 * Simple Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class SimpleJob extends BaseObject implements JobInterface
{
    public $uid;

    public function execute($queue)
    {
        file_put_contents($this->getFileName(), '');
    }

    public function getFileName()
    {
        return Yii::getAlias("@runtime/job-{$this->uid}.lock");
    }
}
