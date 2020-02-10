<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Events;

use Throwable;
use Yiisoft\Yii\Queue\MessageInterface;

interface JobFailureInterface extends EventInterface, StoppableEventInterface
{
    public function getMessage(): MessageInterface;
    public function getException(): Throwable;

    /**
     * Exception won't be thrown if propagation is stopped
     */
    public function stopPropagation(): void;
}
