<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use Yiisoft\Queue\Cli\LoopInterface;

/**
 * Stub loop.
 */
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
