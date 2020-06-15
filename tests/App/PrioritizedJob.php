<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Job\PrioritisedJobInterface;

class PrioritizedJob extends SimpleJob implements PrioritisedJobInterface
{
    public function getPriority(): int
    {
        return 1;
    }
}
