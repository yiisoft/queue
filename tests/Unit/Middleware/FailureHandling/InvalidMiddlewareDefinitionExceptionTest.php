<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Yii\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestCallableMiddleware;

final class InvalidMiddlewareDefinitionExceptionTest extends TestCase
{
    public function dataBase(): array
    {
        return [
            [
                'test',
                '"test"',
            ],
            [
                new TestCallableMiddleware(),
                'an instance of "Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestCallableMiddleware"',
            ],
            [
                [TestCallableMiddleware::class, 'notExistsAction'],
                '["Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestCallableMiddleware", "notExistsAction"]',
            ],
            [
                ['class' => TestCallableMiddleware::class, 'index'],
                '["class" => "Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling\Support\TestCallableMiddleware", "index"]',
            ],
        ];
    }

    /**
     * @dataProvider dataBase
     */
    public function testBase(mixed $definition, string $expected): void
    {
        $exception = new InvalidMiddlewareDefinitionException($definition);
        self::assertStringEndsWith('. Got ' . $expected . '.', $exception->getMessage());
    }

    public function dataUnknownDefinition(): array
    {
        return [
            [42],
            [[new stdClass()]],
        ];
    }

    /**
     * @dataProvider dataUnknownDefinition
     */
    public function testUnknownDefinition(mixed $definition): void
    {
        $exception = new InvalidMiddlewareDefinitionException($definition);
        self::assertSame(
            'Parameter should be either middleware class name or a callable.',
            $exception->getMessage()
        );
    }
}
