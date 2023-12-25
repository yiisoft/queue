<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Integration;

use Psr\Log\NullLogger;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareFactoryConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFactoryFailureInterface;
use Yiisoft\Yii\Queue\Tests\Support\StackMessageHandler;
use Yiisoft\Yii\Queue\Tests\TestCase;
use Yiisoft\Yii\Queue\Worker\Worker;

final class MessageConsumingTest extends TestCase
{
    public function testMessagesConsumed(): void
    {
        $stackMessageHandler = new StackMessageHandler();
        $container = new SimpleContainer([StackMessageHandler::class => $stackMessageHandler]);
        $worker = new Worker(
            [],
            new NullLogger(),
            new Injector($container),
            $container,
            new ConsumeMiddlewareDispatcher($this->createMock(MiddlewareFactoryConsumeInterface::class)),
            new FailureMiddlewareDispatcher($this->createMock(MiddlewareFactoryFailureInterface::class), [])
        );

        $messages = [1, 'foo', 'bar-baz'];
        foreach ($messages as $message) {
            $worker->process(new Message(StackMessageHandler::class, $message), $this->getQueue());
        }

        $data = array_map(fn (MessageInterface $message) => $message->getData(), $stackMessageHandler->processedMessages);
        $this->assertEquals($messages, $data);
    }
}
