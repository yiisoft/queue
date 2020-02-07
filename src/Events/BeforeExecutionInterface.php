<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Events;

use Yiisoft\Yii\Queue\MessageInterface;

interface BeforeExecutionInterface extends EventInterface, StoppableEventInterface
{
    public function getMessage(): MessageInterface;
}
