<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Events;

use Yiisoft\Yii\Queue\MessageInterface;

interface AfterPushInterface extends EventInterface
{
    public function getMessage(): MessageInterface;
}
