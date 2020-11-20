<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Cli;

use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Yii\Queue\Event\MemoryLimitReached;

trait SoftLimitTrait
{
    abstract protected function getMemoryLimit(): int;

    abstract protected function getEventDispatcher(): EventDispatcherInterface;

    protected function memoryLimitReached(): bool
    {
        $limit = $this->getMemoryLimit();

        if ($limit !== 0) {
            $usage = memory_get_usage(true);

            if ($usage >= $limit) {
                $this->getEventDispatcher()->dispatch(new MemoryLimitReached($limit, $usage));
                return true;
            }
        }

        return false;
    }
}
