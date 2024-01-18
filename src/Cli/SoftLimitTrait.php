<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Cli;

trait SoftLimitTrait
{
    abstract protected function getMemoryLimit(): int;

    protected function memoryLimitReached(): bool
    {
        $limit = $this->getMemoryLimit();

        if ($limit !== 0) {
            $usage = memory_get_usage(true);

            if ($usage >= $limit) {
                return true;
            }
        }

        return false;
    }
}
