<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message\Handler\Resolver;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Message\Handler\HandlerNotFoundException;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Message\Handler\InvalidHandlerConfigurationException;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Tests\App\FakeHandler;
use Yiisoft\Queue\Tests\App\StaticMessageHandler;

final class HandlerResolverTest extends TestCase
{
    #[DataProvider('handlerDefinitionDataProvider')]
    public function testResolve(mixed $handler, array $containerServices): void
    {
        $message = new GenericMessage('simple', ['test-data']);
        $container = new SimpleContainer($containerServices);
        $resolver = new HandlerResolver(['simple' => $handler], $container);

        $resolvedHandler = $resolver->resolve($message->getType());
        $resolvedHandler->handle($message);

        $processedMessages = FakeHandler::$processedMessages;
        FakeHandler::$processedMessages = [];

        $this->assertSame([$message], $processedMessages);
    }

    public static function handlerDefinitionDataProvider(): iterable
    {
        yield 'definition' => [
            FakeHandler::class,
            [FakeHandler::class => new FakeHandler()],
        ];
        yield 'definition-object' => [
            [new FakeHandler(), 'handle'],
            [],
        ];
        yield 'definition-class' => [
            [FakeHandler::class, 'handle'],
            [FakeHandler::class => new FakeHandler()],
        ];
        yield 'definition-not-found-class-but-exist-in-container' => [
            ['not-found-class-name', 'handle'],
            ['not-found-class-name' => new FakeHandler()],
        ];
        yield 'callable' => [
            function (MessageInterface $message) {
                FakeHandler::$processedMessages[] = $message;
            },
            [],
        ];
    }

    public function testResolveCachesResolvedHandler(): void
    {
        $container = new SimpleContainer([FakeHandler::class => new FakeHandler()]);
        $resolver = new HandlerResolver(['simple' => FakeHandler::class], $container);

        $this->assertSame($resolver->resolve('simple'), $resolver->resolve('simple'));
    }

    public function testResolveStaticMethodHandler(): void
    {
        $container = new SimpleContainer();
        $resolver = new HandlerResolver(
            ['static-handler' => StaticMessageHandler::handle(...)],
            $container,
        );

        StaticMessageHandler::$wasHandled = false;
        $resolvedHandler = $resolver->resolve('static-handler');
        $resolvedHandler->handle(new GenericMessage('static-handler', null));

        $this->assertTrue(StaticMessageHandler::$wasHandled);
    }

    public function testResolveThrowsWhenDefinitionMethodUndefined(): void
    {
        $this->expectException(InvalidHandlerConfigurationException::class);
        $this->expectExceptionMessage('Queue handler for message type "simple" is configured incorrectly');

        $container = new SimpleContainer([FakeHandler::class => new FakeHandler()]);
        $resolver = new HandlerResolver(
            ['simple' => [FakeHandler::class, 'undefinedMethod']],
            $container,
        );

        $resolver->resolve('simple');
    }

    public function testResolveThrowsWhenDefinitionClassUndefined(): void
    {
        $this->expectException(InvalidHandlerConfigurationException::class);
        $this->expectExceptionMessage('Queue handler for message type "simple" is configured incorrectly');

        $container = new SimpleContainer([FakeHandler::class => new FakeHandler()]);
        $resolver = new HandlerResolver(
            ['simple' => ['UndefinedClass', 'handle']],
            $container,
        );

        $resolver->resolve('simple');
    }

    public function testResolveThrowsWhenDefinitionClassNotFoundInContainer(): void
    {
        $this->expectException(InvalidHandlerConfigurationException::class);
        $this->expectExceptionMessage('Queue handler for message type "simple" is configured incorrectly');

        $container = new SimpleContainer();
        $resolver = new HandlerResolver(
            ['simple' => [FakeHandler::class, 'handle']],
            $container,
        );

        $resolver->resolve('simple');
    }

    public function testResolveThrowsWhenHandlerNotFoundInContainer(): void
    {
        $this->expectException(HandlerNotFoundException::class);
        $this->expectExceptionMessage('Queue handler for message type "nonexistent" does not exist');

        $container = new SimpleContainer();
        $resolver = new HandlerResolver([], $container);

        $resolver->resolve('nonexistent');
    }

    public function testResolveThrowsWhenHandlerInContainerNotImplementingInterface(): void
    {
        $this->expectException(InvalidHandlerConfigurationException::class);
        $this->expectExceptionMessage('Queue handler for message type "invalid" is configured incorrectly');

        $container = new SimpleContainer([
            'invalid' => new class {
                public function handle(): void {}
            },
        ]);
        $resolver = new HandlerResolver([], $container);

        $resolver->resolve('invalid');
    }

    public function testResolveThrowsWhenMessageTypeIsEmpty(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Message type cannot be empty.');

        $container = new SimpleContainer();
        $resolver = new HandlerResolver([], $container);

        $resolver->resolve('');
    }
}
