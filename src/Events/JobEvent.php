<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Events;

/**
 * Job Event.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class JobEvent
{
    /**
     * @var \Yiisoft\Yii\Queue\Queue
     *            {@inheritdoc}
     */
    public $sender;
    /**
     * @var string|null unique id of a job
     */
    public $id;
    /**
     * @var \Yiisoft\Yii\Queue\JobInterface|null
     */
    public $job;
    /**
     * @var int time to reserve in seconds of the job
     */
    public $ttr;

    public function __construct(string $name, $id, $job, $ttr)
    {
        parent::__construct($name);
        $this->id = $id;
        $this->job = $job;
        $this->ttr = $ttr;
    }
}
