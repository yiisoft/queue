<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use Psr\Log\NullLogger;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Queue\Message\HandlerEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\MiddlewareFactoryInterface;
use Yiisoft\Queue\Tests\Support\StackMessageHandler;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;

final class MessageConsumingTest extends TestCase
{
    public function testMessagesConsumed(): void
    {
        $stackMessageHandler = new StackMessageHandler();

        $collection = (new ListenerCollection());
        $collection = $collection->add(fn (Message $message) => $stackMessageHandler->handle($message));
        $worker = new Worker(
            new NullLogger(),
            new Dispatcher(new Provider($collection)),
            $this->createContainer([StackMessageHandler::class => $stackMessageHandler]),
            new MiddlewareDispatcher($this->createMock(MiddlewareFactoryInterface::class)),
            new MiddlewareDispatcher($this->createMock(MiddlewareFactoryInterface::class), [])
        );

        $messages = [1, 'foo', 'bar-baz'];
        foreach ($messages as $message) {
            $worker->process(
                new HandlerEnvelope(
                    new Message($message),
                    StackMessageHandler::class
                ),
                $this->getQueue()
            );
        }

        $data = array_map(
            fn (MessageInterface $message) => $message->getData(),
            $stackMessageHandler->processedMessages
        );
        $this->assertEquals($messages, $data);
    }
}
