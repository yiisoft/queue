<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Job\JobInterface;

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
