<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Cli;

use Psr\EventDispatcher\EventDispatcherInterface;

class SimpleLoop implements LoopInterface
{
    use SoftLimitTrait;

    protected int $memorySoftLimit;
    protected EventDispatcherInterface $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param int $memorySoftLimit Soft RAM limit in bytes. The loop won't let you continue to execute the program if
     *     soft limit is reached. Zero means no limit.
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        int $memorySoftLimit = 0
    ) {
        $this->dispatcher = $dispatcher;
        $this->memorySoftLimit = $memorySoftLimit;
    }

    public function canContinue(): bool
    {
        return !$this->memoryLimitReached();
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    protected function getMemoryLimit(): int
    {
        return $this->memorySoftLimit;
    }
}
