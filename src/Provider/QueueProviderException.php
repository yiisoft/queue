<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use Throwable;

/**
 * Base interface representing a generic exception in a queue provider.
 */
interface QueueProviderException extends Throwable {}
