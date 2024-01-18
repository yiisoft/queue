<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support;

final class InvalidController
{
    public function index(): int
    {
        return 200;
    }
}
