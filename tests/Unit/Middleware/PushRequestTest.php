<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\TestCase;

final class PushRequestTest extends TestCase
{
    public function testImmutable(): void
    {
        $message = new Message('test', 'test');
        $Request = new Request($message, new FakeAdapter());

        $this->assertNotSame($Request, $Request->withAdapter(new FakeAdapter()));
        $this->assertNotSame($Request, $Request->withMessage($message));
    }
}
