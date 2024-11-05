<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Cli;

final class StubLoop implements LoopInterface
{
    public function __construct(
        private readonly bool $canContinue = true,
    ) {
    }

    public function canContinue(): bool
    {
        return $this->canContinue;
    }
}
