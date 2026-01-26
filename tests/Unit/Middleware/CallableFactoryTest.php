<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\InvalidCallableConfigurationException;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class CallableFactoryTest extends TestCase
{
    public function testCreateFromContainerStringInvokable(): void
    {
        $invokable = new class {
            public function __invoke(): string
            {
                return 'ok';
            }
        };
        $container = new SimpleContainer([
            'invokable' => $invokable,
        ]);

        $factory = new CallableFactory($container);
        $callable = $factory->create('invokable');

        self::assertIsCallable($callable);
        self::assertSame('ok', $callable());
    }

    public function testCreateFromStaticMethodArray(): void
    {
        $class = new class {
            public static function ping(): string
            {
                return 'pong';
            }
        };
        $className = $class::class;
        $container = new SimpleContainer();

        $factory = new CallableFactory($container);
        $callable = $factory->create([$className, 'ping']);

        self::assertIsCallable($callable);
        self::assertSame('pong', $callable());
    }

    public function testCreateFromContainerObjectMethod(): void
    {
        $service = new class {
            public function go(): string
            {
                return 'ok';
            }
        };
        $className = $service::class;
        $container = new SimpleContainer([
            $className => $service,
        ]);

        $factory = new CallableFactory($container);
        $callable = $factory->create([$className, 'go']);

        self::assertIsCallable($callable);
        self::assertSame('ok', $callable());
    }

    public function testFriendlyException(): void
    {
        $e = new InvalidCallableConfigurationException();
        self::assertSame('Invalid callable configuration.', $e->getName());
        self::assertNotNull($e->getSolution());
        self::assertStringContainsString('callable', (string) $e->getSolution());
    }
}
