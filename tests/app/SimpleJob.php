<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests\app;

use yii\base\BaseObject;
use yii\helpers\Yii;
use Yiisoft\Yii\Queue\JobInterface;

/**
 * Simple Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class SimpleJob extends BaseObject implements JobInterface
{
    public $uid;

    public function __construct($uid = null)
    {
        $this->uid = $uid;
    }

    public function execute($queue)
    {
        file_put_contents($this->getFileName(), '');
    }

    public function getFileName()
    {
        return Yii::getAlias("@runtime/job-{$this->uid}.lock");
    }
}
