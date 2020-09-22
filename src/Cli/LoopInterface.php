<?php

namespace Yiisoft\Yii\Queue\Cli;

interface LoopInterface
{
    /**
     * @return bool Whether to continue listening of the queue.
     */
    public function canContinue(): bool;
}
