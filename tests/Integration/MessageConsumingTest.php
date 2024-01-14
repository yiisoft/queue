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
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\MiddlewareFactoryInterface;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;

final class MessageConsumingTest extends TestCase
{
    private array $messagesProcessed;

    public function testMessagesConsumed(): void
    {
        $this->messagesProcessed = [];

        $container = $this->createMock(ContainerInterface::class);
        $worker = new Worker(
            ['test' => fn (MessageInterface $message): mixed => $this->messagesProcessed[] = $message->getData()],
            new NullLogger(),
            new Injector($container),
            $container,
            new MiddlewareDispatcher($this->createMock(MiddlewareFactoryInterface::class)),
            new FailureMiddlewareDispatcher($this->createMock(MiddlewareFactoryFailureInterface::class), [])
        );

        $messages = [1, 'foo', 'bar-baz'];
        foreach ($messages as $message) {
            $worker->process(new Message('test', $message), $this->getQueue());
        }

        $this->assertEquals($messages, $this->messagesProcessed);
    }
}
