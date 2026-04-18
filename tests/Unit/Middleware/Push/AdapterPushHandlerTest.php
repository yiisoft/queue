<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Tests\App\FakeAdapter;

final class AdapterPushHandlerTest extends TestCase
{
    public function testHandlePushUsesAdapter(): void
    {
        $adapter = new FakeAdapter();
        $handler = new AdapterPushHandler($adapter);
        $message = new Message('handler', 'data');
        $request = new PushRequest($message);

        $result = $handler->handlePush($request);

        self::assertSame($message, $result->getMessage());
        self::assertSame([$message], $adapter->pushMessages);
    }
}
