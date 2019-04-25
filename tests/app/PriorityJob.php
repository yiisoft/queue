<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests\App;

use yii\base\BaseObject;
use yii\helpers\Yii;
use Yiisoft\Yii\Queue\JobInterface;

/**
 * Priority Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class PriorityJob extends BaseObject implements JobInterface
{
    public $number;

    public function execute($queue)
    {
        file_put_contents(self::getFileName(), $this->number, FILE_APPEND);
    }

    public static function getFileName()
    {
        return Yii::getAlias('@runtime/job-priority.log');
    }
}
