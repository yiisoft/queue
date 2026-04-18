<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Adapter;

/**
 * Marker for adapters whose messages must be handled in the same process right after push.
 */
interface ImmediateProcessingAdapterInterface
{
}
