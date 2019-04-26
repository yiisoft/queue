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
use Yiisoft\Yii\Queue\RetryableJobInterface;

/**
 * Retry Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class RetryJob extends BaseObject implements RetryableJobInterface
{
    public $uid;

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function execute($queue)
    {
        file_put_contents($this->getFileName(), 'a', FILE_APPEND);

        throw new \Exception('Planned error.');
    }

    public function getFileName()
    {
        return Yii::getAlias("@runtime/job-{$this->uid}.lock");
    }

    public function getTtr(): int
    {
        return 2;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < 2;
    }
}
