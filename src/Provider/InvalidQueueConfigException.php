<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use LogicException;

/**
 * Thrown when queue configuration is invalid.
 */
final class InvalidQueueConfigException extends LogicException implements QueueProviderException {}
