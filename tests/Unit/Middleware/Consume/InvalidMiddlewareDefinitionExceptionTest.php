<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Tests\Unit\Middleware\Support\TestCallableMiddleware;

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
                sprintf(
                    'an instance of "%s"',
                    TestCallableMiddleware::class,
                ),
            ],
            [
                [TestCallableMiddleware::class, 'notExistsAction'],
                sprintf(
                    '["%s", "notExistsAction"]',
                    TestCallableMiddleware::class,
                ),
            ],
            [
                ['class' => TestCallableMiddleware::class, 'index'],
                sprintf(
                    '["class" => "%s", "index"]',
                    TestCallableMiddleware::class,
                ),
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
