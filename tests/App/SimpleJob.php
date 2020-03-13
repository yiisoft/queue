<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Jobs\JobInterface;

/**
 * Simple Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class SimpleJob implements JobInterface
{
    public bool $executed = false;

    public function execute(): void
    {
        $this->executed = true;
    }
}
