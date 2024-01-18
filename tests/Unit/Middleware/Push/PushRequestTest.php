<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\TestCase;

final class PushRequestTest extends TestCase
{
    public function testImmutable(): void
    {
        $message = new Message('test', 'test');
        $pushRequest = new PushRequest($message, new FakeAdapter());

        $this->assertNotSame($pushRequest, $pushRequest->withAdapter(new FakeAdapter()));
        $this->assertNotSame($pushRequest, $pushRequest->withMessage($message));
    }
}
