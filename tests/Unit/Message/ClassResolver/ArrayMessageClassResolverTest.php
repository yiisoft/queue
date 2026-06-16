<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message\ClassResolver;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\ClassResolver\ArrayMessageClassResolver;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Tests\Unit\Support\TestMessage;

final class ArrayMessageClassResolverTest extends TestCase
{
    public function testResolveRegisteredType(): void
    {
        $resolver = new ArrayMessageClassResolver(['test' => TestMessage::class]);

        $this->assertSame(TestMessage::class, $resolver->resolve('test'));
    }

    public function testResolveUnregisteredTypeReturnsNull(): void
    {
        $resolver = new ArrayMessageClassResolver(['test' => TestMessage::class]);

        $this->assertNull($resolver->resolve('unknown'));
    }

    public function testResolveWithEmptyMapReturnsNull(): void
    {
        $resolver = new ArrayMessageClassResolver();

        $this->assertNull($resolver->resolve('test'));
    }

    public function testResolveMultipleTypes(): void
    {
        $resolver = new ArrayMessageClassResolver([
            'generic' => GenericMessage::class,
            'test' => TestMessage::class,
        ]);

        $this->assertSame(GenericMessage::class, $resolver->resolve('generic'));
        $this->assertSame(TestMessage::class, $resolver->resolve('test'));
        $this->assertNull($resolver->resolve('not-registered'));
    }
}
