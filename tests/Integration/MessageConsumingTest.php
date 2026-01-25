<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\MiddlewareFactoryConsumeInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFactoryFailureInterface;
use Yiisoft\Queue\Tests\Integration\Support\TestHandler;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;

final class MessageConsumingTest extends TestCase
{
    private array $messagesProcessed;
    private array $messagesProcessedSecond;

    public function testMessagesConsumed(): void
    {
        $this->messagesProcessed = [];
        $this->messagesProcessedSecond = [];

        $container = $this->createMock(ContainerInterface::class);
        $worker = new Worker(
            [
                'test' => fn(MessageInterface $message): mixed => $this->messagesProcessed[] = $message->getData(),
                'test2' => fn(MessageInterface $message): mixed => $this->messagesProcessedSecond[] = $message->getData(),
            ],
            new NullLogger(),
            new Injector($container),
            $container,
            new ConsumeMiddlewareDispatcher($this->createMock(MiddlewareFactoryConsumeInterface::class)),
            new FailureMiddlewareDispatcher($this->createMock(MiddlewareFactoryFailureInterface::class), []),
        );

        $messages = [1, 'foo', 'bar-baz'];
        foreach ($messages as $message) {
            $worker->process(new Message('test', $message), $this->getQueue());
            $worker->process(new Message('test2', $message), $this->getQueue());
        }

        $this->assertEquals($messages, $this->messagesProcessed);
        $this->assertEquals($messages, $this->messagesProcessedSecond);
    }

    public function testMessagesConsumedByHandlerClass(): void
    {
        $handler = new TestHandler();
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(TestHandler::class)->willReturn($handler);
        $container->method('has')->with(TestHandler::class)->willReturn(true);
        $worker = new Worker(
            [],
            new NullLogger(),
            new Injector($container),
            $container,
            new ConsumeMiddlewareDispatcher($this->createMock(MiddlewareFactoryConsumeInterface::class)),
            new FailureMiddlewareDispatcher($this->createMock(MiddlewareFactoryFailureInterface::class), []),
        );

        $messages = [1, 'foo', 'bar-baz'];
        foreach ($messages as $message) {
            $worker->process(new Message(TestHandler::class, $message), $this->getQueue());
        }

        $this->assertEquals($messages, $handler->messagesProcessed);
    }
}
