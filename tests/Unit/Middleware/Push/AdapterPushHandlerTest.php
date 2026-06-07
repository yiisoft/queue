<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Queue\Stubs\InMemoryAdapter;

final class AdapterPushHandlerTest extends TestCase
{
    public function testHandlePushUsesAdapter(): void
    {
        $adapter = new InMemoryAdapter();
        $handler = new AdapterPushHandler($adapter);
        $message = new GenericMessage('handler', 'data');

        $handler->handlePush($message);

        self::assertSame([$message], $adapter->getMessagesList());
    }
}
