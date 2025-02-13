<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\App;

class StaticMessageHandler
{
    public static bool $wasHandled = false;

    public static function handle(): void
    {
        self::$wasHandled = true;
    }
}
