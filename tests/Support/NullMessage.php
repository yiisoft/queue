<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Support;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageTrait;

class NullMessage implements MessageInterface
{
    use MessageTrait;
}
