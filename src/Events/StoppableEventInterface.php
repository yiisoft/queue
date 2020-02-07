<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Events;

use Psr\EventDispatcher\StoppableEventInterface as BaseInterface;

interface StoppableEventInterface extends BaseInterface
{
    public function stopPropagation(): void;
}
