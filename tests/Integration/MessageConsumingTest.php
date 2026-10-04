<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerFinalHandler;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactory;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
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
            new NullLogger(),
            new WorkerMiddlewareDispatcher(
                new WorkerMiddlewareFactory($container),
                [],
                new WorkerFinalHandler(
                    new HandlerResolver(
                        [
                            'test' => fn(MessageInterface $message): mixed => $this->messagesProcessed[] = $message->getPayload(),
                            'test2' => fn(MessageInterface $message): mixed => $this->messagesProcessedSecond[] = $message->getPayload(),
                        ],
                        $container,
                    ),
                    new ConsumeMiddlewareDispatcher($this->createMock(ConsumeMiddlewareFactoryInterface::class)),
                ),
            ),
            new FailureMiddlewareDispatcher($this->createMock(FailureMiddlewareFactoryInterface::class), []),
        );

        $messages = [1, 'foo', 'bar-baz'];
        foreach ($messages as $message) {
            $worker->process(new GenericMessage('test', $message), 'yii-queue');
            $worker->process(new GenericMessage('test2', $message), 'yii-queue');
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
            new NullLogger(),
            new WorkerMiddlewareDispatcher(
                new WorkerMiddlewareFactory($container),
                [],
                new WorkerFinalHandler(
                    new HandlerResolver([], $container),
                    new ConsumeMiddlewareDispatcher($this->createMock(ConsumeMiddlewareFactoryInterface::class)),
                ),
            ),
            new FailureMiddlewareDispatcher($this->createMock(FailureMiddlewareFactoryInterface::class), []),
        );

        $messages = [1, 'foo', 'bar-baz'];
        foreach ($messages as $message) {
            $worker->process(new GenericMessage(TestHandler::class, $message), 'yii-queue');
        }

        $this->assertEquals($messages, $handler->messagesProcessed);
    }
}
