<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Shared;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageTrait;

class NullMessage implements MessageInterface
{
    use MessageTrait;
}
