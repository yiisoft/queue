<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\InvalidCallableConfigurationException;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class CallableFactoryTest extends TestCase
{
    #[DataProvider('positiveDefinitionsProvider')]
    public function testCreatePositive(mixed $definition, array $arguments, mixed $expectedResult, SimpleContainer $container): void
    {
        $factory = new CallableFactory($container);
        $callable = $factory->create($definition);

        self::assertIsCallable($callable);
        self::assertSame($expectedResult, $callable(...$arguments));
    }

    public static function positiveDefinitionsProvider(): iterable
    {
        yield 'closure' => [
            static fn (): string => 'ok',
            [],
            'ok',
            new SimpleContainer(),
        ];

        yield 'callable string' => [
            'strlen',
            ['foo'],
            3,
            new SimpleContainer(),
        ];

        $invokable = new class () {
            public function __invoke(): string
            {
                return 'ok';
            }
        };

        yield 'container string invokable' => [
            'invokable',
            [],
            'ok',
            new SimpleContainer([
                'invokable' => $invokable,
            ]),
        ];

        $class = new class () {
            public static function ping(): string
            {
                return 'pong';
            }
        };
        $className = $class::class;

        yield 'static method array' => [
            [$className, 'ping'],
            [],
            'pong',
            new SimpleContainer(),
        ];

        $serviceFromContainer = new class () {
            public function go(): string
            {
                return 'ok';
            }
        };
        $serviceClassName = $serviceFromContainer::class;

        yield 'container object method' => [
            [$serviceClassName, 'go'],
            [],
            'ok',
            new SimpleContainer([
                $serviceClassName => $serviceFromContainer,
            ]),
        ];

        $service = new class () {
            public function go(): string
            {
                return 'ok';
            }
        };

        yield 'object method array' => [
            $service->go(...),
            [],
            'ok',
            new SimpleContainer(),
        ];

        $serviceById = new class () {
            public function go(): string
            {
                return 'ok';
            }
        };

        yield 'container id method' => [
            ['service', 'go'],
            [],
            'ok',
            new SimpleContainer([
                'service' => $serviceById,
            ]),
        ];
    }

    #[DataProvider('negativeDefinitionsProvider')]
    public function testCreateNegative(mixed $definition, SimpleContainer $container): void
    {
        $factory = new CallableFactory($container);

        $this->expectException(InvalidCallableConfigurationException::class);
        $factory->create($definition);
    }

    public static function negativeDefinitionsProvider(): iterable
    {
        yield 'null' => [
            null,
            new SimpleContainer(),
        ];

        yield 'string not callable and not in container' => [
            'notExistingCallable',
            new SimpleContainer(),
        ];

        yield 'container string not callable' => [
            'notCallable',
            new SimpleContainer([
                'notCallable' => new \stdClass(),
            ]),
        ];

        $service = new class () {
            public function go(): string
            {
                return 'ok';
            }
        };

        yield 'object method array invalid method' => [
            [$service, 'missing'],
            new SimpleContainer(),
        ];

        $class = new class () {
            public function ping(): string
            {
                return 'pong';
            }
        };
        $className = $class::class;

        yield 'non-static method array without container' => [
            [$className, 'ping'],
            new SimpleContainer(),
        ];

        yield 'invalid array definition' => [
            ['onlyOneElement'],
            new SimpleContainer(),
        ];

        $serviceWithoutMethod = new class () {
            public function go(): string
            {
                return 'ok';
            }
        };
        $serviceClassName = $serviceWithoutMethod::class;

        yield 'class in container but method missing' => [
            [$serviceClassName, 'missing'],
            new SimpleContainer([
                $serviceClassName => $serviceWithoutMethod,
            ]),
        ];
    }

    public function testFriendlyException(): void
    {
        $e = new InvalidCallableConfigurationException();
        self::assertSame('Invalid callable configuration.', $e->getName());
        self::assertNotNull($e->getSolution());
        self::assertStringContainsString('callable', (string)$e->getSolution());
    }
}
