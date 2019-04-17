<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use yii\base\Action as BaseAction;
use yii\console\Controller as ConsoleController;
use yii\exceptions\InvalidConfigException;

/**
 * Base Command Action.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Action extends BaseAction
{
    /**
     * @var Queue
     */
    public $queue;
    /**
     * @var Command|ConsoleController
     */
    public $controller;

    /**
     * {@inheritdoc}
     */
    public function __construct($id, $controller)
    {
        parent::__construct($id, $controller);

        if (!$this->queue && ($this->controller instanceof Command)) {
            $this->queue = $this->controller->queue;
        }
        if (!($this->controller instanceof ConsoleController)) {
            throw new InvalidConfigException('The controller must be console controller.');
        }
        if (!($this->queue instanceof Queue)) {
            throw new InvalidConfigException('The queue must be cli queue.');
        }
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function format($string)
    {
        return call_user_func_array([$this->controller, 'ansiFormat'], func_get_args());
    }
}
