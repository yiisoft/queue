<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support;

final class InvalidController
{
    public function index(): int
    {
        return 200;
    }
}
