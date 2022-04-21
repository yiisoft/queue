<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push\Support;

final class InvalidController
{
    public function index(): int
    {
        return 200;
    }
}
