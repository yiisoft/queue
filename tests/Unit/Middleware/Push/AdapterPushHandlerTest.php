<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Tests\App\FakeAdapter;

final class AdapterPushHandlerTest extends TestCase
{
    public function testHandlePushThrowsWhenNoAdapter(): void
    {
        $handler = new AdapterPushHandler();
        $request = new PushRequest(new Message('handler', 'data'), null);

        $this->expectException(AdapterNotConfiguredException::class);
        $handler->handlePush($request);
    }

    public function testHandlePushUsesAdapter(): void
    {
        $handler = new AdapterPushHandler();
        $adapter = new FakeAdapter();
        $message = new Message('handler', 'data');
        $request = new PushRequest($message, $adapter);

        $result = $handler->handlePush($request);

        self::assertSame($message, $result->getMessage());
        self::assertSame([$message], $adapter->pushMessages);
    }
}
