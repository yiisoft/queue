<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use Psr\Log\NullLogger;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Message\HandlerEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFactoryFailureInterface;
use Yiisoft\Queue\Tests\Support\StackMessageHandler;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\MiddlewareFactoryInterface;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class MessageConsumingTest extends TestCase
{
    public function testMessagesConsumed(): void
    {
        $stackMessageHandler = new StackMessageHandler();
        $container = new SimpleContainer([StackMessageHandler::class => $stackMessageHandler]);
        $worker = new Worker(
            new NullLogger(),
            new Injector($container),
            $container,
            new MiddlewareDispatcher($this->createMock(MiddlewareFactoryInterface::class)),
            new FailureMiddlewareDispatcher($this->createMock(MiddlewareFactoryFailureInterface::class), [])
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

        $data = array_map(fn (MessageInterface $message) => $message->getData(), $stackMessageHandler->processedMessages);
        $this->assertEquals($messages, $data);
    }
}
