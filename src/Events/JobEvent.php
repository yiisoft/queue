<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Events;

use Yiisoft\Yii\Queue\JobInterface;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\RetryableJobInterface;

/**
 * Job Event.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class JobEvent
{
    /**
     * @var Queue
     */
    public Queue $sender;
    /**
     * @var string
     */
    public string $name;
    /**
     * @var string|null unique id of a job
     */
    public ?string $id;
    /**
     * @var JobInterface|RetryableJobInterface
     */
    public ?JobInterface $job;
    /**
     * @var int time to reserve in seconds of the job
     */
    public int $ttr;

    public function __construct(string $name, string $id, ?JobInterface $job, int $ttr)
    {
        $this->name = $name;
        $this->id = $id;
        $this->job = $job;
        $this->ttr = $ttr;
        $this->name = $name;
    }
}
