<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Debug;

use yii\exceptions\NotSupportedException;
use yii\helpers\VarDumper;
use Yiisoft\Yii\Queue\Events\PushEvent;
use Yiisoft\Yii\Queue\JobInterface;
use Yiisoft\Yii\Queue\Queue;
use yii\view\ViewContextInterface;

/**
 * Debug Panel.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Panel extends \Yiisoft\Debug\Panel implements ViewContextInterface
{
    private $_jobs = [];

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Queue';
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        PushEvent::on(Queue::class, PushEvent::AFTER, function (PushEvent $event) {
            $this->_jobs[] = $this->getPushData($event);
        });
    }

    /**
     * @param PushEvent $event
     *
     * @return array
     */
    protected function getPushData(PushEvent $event)
    {
        $data = [];
        foreach ($this->app->getComponents(false) as $id => $component) {
            if ($component === $event->sender) {
                $data['sender'] = $id;
                break;
            }
        }
        $data['id'] = $event->id;
        $data['ttr'] = $event->ttr;
        $data['delay'] = $event->delay;
        $data['priority'] = $event->priority;
        if ($event->job instanceof JobInterface) {
            $data['class'] = get_class($event->job);
            $data['properties'] = [];
            foreach (get_object_vars($event->job) as $property => $value) {
                $data['properties'][$property] = VarDumper::dumpAsString($value);
            }
        } else {
            $data['data'] = VarDumper::dumpAsString($event->job);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        return ['jobs' => $this->_jobs];
    }

    /**
     * {@inheritdoc}
     */
    public function getViewPath()
    {
        return __DIR__.'/views';
    }

    /**
     * {@inheritdoc}
     */
    public function getSummary()
    {
        return $this->app->view->render('summary', [
            'url'   => $this->getUrl(),
            'count' => isset($this->data['jobs']) ? count($this->data['jobs']) : 0,
        ], $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getDetail()
    {
        $jobs = isset($this->data['jobs']) ? $this->data['jobs'] : [];
        foreach ($jobs as &$job) {
            $job['status'] = 'unknown';
            /** @var Queue $queue */
            if ($queue = $this->app->get($job['sender'], false)) {
                try {
                    if ($queue->isWaiting($job['id'])) {
                        $job['status'] = 'waiting';
                    } elseif ($queue->isReserved($job['id'])) {
                        $job['status'] = 'reserved';
                    } elseif ($queue->isDone($job['id'])) {
                        $job['status'] = 'done';
                    }
                } catch (NotSupportedException $e) {
                } catch (\Exception $e) {
                    $job['status'] = $e->getMessage();
                }
            }
        }
        unset($job);

        return $this->app->view->render('detail', compact('jobs'), $this);
    }
}
