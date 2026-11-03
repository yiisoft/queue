<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Stubs\InMemoryAdapter;

final class AdapterPushHandlerTest extends TestCase
{
    public function testHandlePushUsesAdapter(): void
    {
        $adapter = new InMemoryAdapter();
        $handler = new AdapterPushHandler($adapter);
        $message = new GenericMessage('handler', 'data');

        $result = $handler->handlePush(new PushRequest($message, 'queue'));

        $enrichedMessage = $result->getMessage();

        self::assertInstanceOf(IdEnvelope::class, $enrichedMessage);
        self::assertSame(0, $enrichedMessage->getId());
        self::assertSame($message, $enrichedMessage->getMessage());
        self::assertSame([$message], $adapter->getMessagesList());
    }
}
