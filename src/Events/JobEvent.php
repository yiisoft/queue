<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Events;

use Yiisoft\Yii\Queue\JobInterface;

/**
 * Job Event.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class JobEvent
{
    /**
     * @var \Yiisoft\Yii\Queue\Queue
     */
    public $sender;
    /**
     * @var string|null unique id of a job
     */
    public $id;
    /**
     * @var \Yiisoft\Yii\Queue\JobInterface
     */
    public $job;
    /**
     * @var int time to reserve in seconds of the job
     */
    public $ttr;

    public function __construct($id, JobInterface $job, int $ttr)
    {
        $this->id = $id;
        $this->job = $job;
        $this->ttr = $ttr;
    }
}
