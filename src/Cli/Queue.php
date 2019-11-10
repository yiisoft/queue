<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Cli;

use yii\helpers\Yii;
use Yiisoft\Factory\Exceptions\InvalidConfigException;
use Yiisoft\Strings\Inflector;
use Yiisoft\Yii\Console\Application as ConsoleApp;
use Yiisoft\Yii\Queue\Queue as BaseQueue;

/**
 * Queue with CLI.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Queue extends BaseQueue
{
    /**
     * @var array|string
     *
     * @since 2.0.2
     */
    public $loopConfig = SignalLoop::class;
    /**
     * @var string command class name
     */
    public $commandClass = Command::class;
    /**
     * @var array of additional options of command
     */
    public $commandOptions = [];
    /**
     * @var callable|null
     *
     * @internal for worker command only
     */
    public $messageHandler;

    /**
     * @var int|null current process ID of a worker.
     *
     * @since 2.0.2
     */
    private $_workerPid;

    /**
     * @throws
     *
     * @return string command id
     */
    protected function getCommandId()
    {
        foreach (Yii::getContainer()->getInstances() as $id => $component) {
            if ($component === $this) {
                return Inflector::camel2id($id);
            }
        }

        throw new InvalidConfigException('Queue must be an application component.');
    }

    /**
     * {@inheritdoc}
     */
    public function bootstrap($app)
    {
        if ($app instanceof ConsoleApp) {
            $app->controllerMap[$this->getCommandId()] = [
                '__class' => $this->commandClass,
                'queue'   => $this,
            ] + $this->commandOptions;
        }
    }

    /**
     * Runs worker.
     *
     * @param callable $handler
     *
     * @return null|int exit code
     *
     * @since 2.0.2
     */
    protected function runWorker(callable $handler)
    {
        $this->_workerPid = getmypid();
        /** @var LoopInterface $loop */
        $loop = Yii::createObject($this->loopConfig, [$this]);

        $event = WorkerEvent::start($loop);
        $this->trigger($event);
        if ($event->exitCode !== null) {
            return $event->exitCode;
        }

        $exitCode = null;

        try {
            $handler(function () use ($loop, $event) {
                $this->trigger(WorkerEvent::loop($event));

                return $event->exitCode === null && $loop->canContinue();
            });
        } finally {
            $this->trigger(WorkerEvent::stop($event));
            $this->_workerPid = null;
        }

        return $event->exitCode;
    }

    /**
     * Gets process ID of a worker.
     *
     * {@inheritdoc}
     *
     * @return int|null
     *
     * @since 2.0.2
     */
    public function getWorkerPid()
    {
        return $this->_workerPid;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleMessage($id, $message, $ttr, $attempt)
    {
        if ($this->messageHandler) {
            return call_user_func($this->messageHandler, $id, $message, $ttr, $attempt);
        }

        return parent::handleMessage($id, $message, $ttr, $attempt);
    }

    /**
     * @param string   $id        of a message
     * @param string   $message
     * @param int      $ttr       time to reserve
     * @param int      $attempt   number
     * @param int|null $workerPid of worker process
     *
     * @return bool
     *
     * @internal for worker command only
     */
    public function execute($id, $message, $ttr, $attempt, $workerPid)
    {
        $this->_workerPid = $workerPid;

        return parent::handleMessage($id, $message, $ttr, $attempt);
    }
}
