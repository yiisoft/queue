<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactoryInterface;
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
        $callableFactory = new CallableFactory($container);
        $worker = new Worker(
            [
                'test' => fn(MessageInterface $message): mixed => $this->messagesProcessed[] = $message->getPayload(),
                'test2' => fn(MessageInterface $message): mixed => $this->messagesProcessedSecond[] = $message->getPayload(),
            ],
            new NullLogger(),
            new Injector($container),
            $container,
            new ConsumeMiddlewareDispatcher($this->createMock(ConsumeMiddlewareFactoryInterface::class)),
            new FailureMiddlewareDispatcher($this->createMock(FailureMiddlewareFactoryInterface::class), []),
            $callableFactory,
        );

        $messages = [1, 'foo', 'bar-baz'];
        foreach ($messages as $message) {
            $worker->process(new GenericMessage('test', $message), $this->getQueue());
            $worker->process(new GenericMessage('test2', $message), $this->getQueue());
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
        $callableFactory = new CallableFactory($container);
        $worker = new Worker(
            [],
            new NullLogger(),
            new Injector($container),
            $container,
            new ConsumeMiddlewareDispatcher($this->createMock(ConsumeMiddlewareFactoryInterface::class)),
            new FailureMiddlewareDispatcher($this->createMock(FailureMiddlewareFactoryInterface::class), []),
            $callableFactory,
        );

        $messages = [1, 'foo', 'bar-baz'];
        foreach ($messages as $message) {
            $worker->process(new GenericMessage(TestHandler::class, $message), $this->getQueue());
        }

        $this->assertEquals($messages, $handler->messagesProcessed);
    }
}
