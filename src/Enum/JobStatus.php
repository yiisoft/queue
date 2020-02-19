<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Enum;

class JobStatus extends AbstractEnum
{
    public const WAITING = 1;
    public const RESERVED = 2;
    public const DONE = 3;
}
