<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use LogicException;

final class InvalidQueueConfigException extends LogicException implements QueueProviderException
{
}
