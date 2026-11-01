<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

enum TestQueue: string
{
    case HIGH_PRIORITY = 'high-priority';
}
