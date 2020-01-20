<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Drivers\Sync;

use Yiisoft\Yii\Queue\Queue as BaseQueue;

/**
 * Sync Queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends BaseQueue
{
    /**
     * @var bool
     */
    public bool $handle = false;

    /**
     * @var array of payloads
     */
    private array $payloads = [];
    /**
     * @var int last pushed ID
     */
    private int $pushedId = 0;
    /**
     * @var int started ID
     */
    private int $startedId = 0;
    /**
     * @var int last finished ID
     */
    private int $finishedId = 0;

    /**
     * {@inheritdoc}
     */
    public function push($job)
    {
        $this->handle();

        return parent::push($job);
    }

    /**
     * Handle job,Confirm whether to bind the event.
     */
    public function handle()
    {
        if ($this->handle) {
            Yii::get('app')->on(RequestEvent::AFTER, function () {
                ob_start();
                $this->run();
                ob_end_clean();
            });
        }
    }

    /**
     * Runs all jobs from queue.
     */
    public function run()
    {
        while (($payload = array_shift($this->payloads)) !== null) {
            [$ttr, $message] = $payload;
            $this->startedId = $this->finishedId + 1;
            $this->handleMessage($this->startedId, $message, $ttr, 1);
            $this->finishedId = $this->startedId;
            $this->startedId = 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        array_push($this->payloads, [$ttr, $message]);

        return ++$this->pushedId;
    }

    /**
     * {@inheritdoc}
     */
    public function status($id)
    {
        if (!is_int($id) || $id <= 0 || $id > $this->pushedId) {
            throw new \InvalidArgumentException("Unknown messages ID: $id.");
        }

        if ($id <= $this->finishedId) {
            return self::STATUS_DONE;
        }

        if ($id === $this->startedId) {
            return self::STATUS_RESERVED;
        }

        return self::STATUS_WAITING;
    }
}
