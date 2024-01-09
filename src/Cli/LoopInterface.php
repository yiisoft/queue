<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Cli;

interface LoopInterface
{
    /**
     * @return bool Whether to continue listening of the queue.
     */
    public function canContinue(): bool;
}
