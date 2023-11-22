<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push;

use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\Push\PushRequest;
use Yiisoft\Yii\Queue\Tests\App\FakeAdapter;
use Yiisoft\Yii\Queue\Tests\TestCase;

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
