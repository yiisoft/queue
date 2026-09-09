<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Stubs\InMemoryAdapter;

final class AdapterPushHandlerTest extends TestCase
{
    public function testHandlePushReturnsAdapterEnrichedMessage(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $message = new GenericMessage('handler', 'data');
        $enriched = $message->withMeta(['adapter-id' => 'id']);
        $adapter->expects(self::once())->method('push')->with($message)->willReturn($enriched);

        $result = (new AdapterPushHandler($adapter))->handlePush(new PushRequest($message, 'queue'));

        self::assertSame($enriched, $result->getMessage());
        self::assertSame('queue', $result->getQueueName());
    }

    public function testHandlePushUsesAdapter(): void
    {
        $adapter = new InMemoryAdapter();
        $handler = new AdapterPushHandler($adapter);
        $message = new GenericMessage('handler', 'data');

        $request = new PushRequest($message, 'test-queue');
        $result = $handler->handlePush($request);

        self::assertSame([$message], $adapter->getMessagesList());
        self::assertNotSame($message, $result->getMessage());
        self::assertSame('test-queue', $result->getQueueName());
    }
}
