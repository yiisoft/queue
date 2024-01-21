<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support\TestCallableMiddleware;

final class InvalidMiddlewareDefinitionExceptionTest extends TestCase
{
    public static function dataBase(): array
    {
        return [
            [
                'test',
                '"test"',
            ],
            [
                new TestCallableMiddleware(),
                'an instance of "Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support\TestCallableMiddleware"',
            ],
            [
                [TestCallableMiddleware::class, 'notExistsAction'],
                '["Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support\TestCallableMiddleware", "notExistsAction"]',
            ],
            [
                ['class' => TestCallableMiddleware::class, 'index'],
                '["class" => "Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support\TestCallableMiddleware", "index"]',
            ],
        ];
    }

    #[DataProvider('dataBase')]
    public function testBase(mixed $definition, string $expected): void
    {
        $exception = new InvalidMiddlewareDefinitionException($definition);
        self::assertStringEndsWith('. Got ' . $expected . '.', $exception->getMessage());
    }

    public static function dataUnknownDefinition(): array
    {
        return [
            [42],
            [[new stdClass()]],
        ];
    }

    #[DataProvider('dataUnknownDefinition')]
    public function testUnknownDefinition(mixed $definition): void
    {
        $exception = new InvalidMiddlewareDefinitionException($definition);
        self::assertSame(
            'Parameter should be either middleware class name or a callable.',
            $exception->getMessage()
        );
    }
}
