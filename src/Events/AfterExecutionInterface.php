<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Events;

use Yiisoft\Yii\Queue\MessageInterface;

interface AfterExecutionInterface extends EventInterface
{
    public function getMessage(): MessageInterface;
}
