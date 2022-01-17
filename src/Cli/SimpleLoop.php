<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Cli;

class SimpleLoop implements LoopInterface
{
    use SoftLimitTrait;

    protected int $memorySoftLimit;

    /**
     * @param int $memorySoftLimit Soft RAM limit in bytes. The loop won't let you continue to execute the program if
     *     soft limit is reached. Zero means no limit.
     */
    public function __construct(
        int $memorySoftLimit = 0
    ) {
        $this->memorySoftLimit = $memorySoftLimit;
    }

    public function canContinue(): bool
    {
        return !$this->memoryLimitReached();
    }

    protected function getMemoryLimit(): int
    {
        return $this->memorySoftLimit;
    }
}
