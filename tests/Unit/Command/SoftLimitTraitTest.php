<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Cli\SoftLimitTrait;

final class SoftLimitTraitTest extends TestCase
{
    public function testMemoryLimitNotReachedWhenLimitIsZero(): void
    {
        $instance = new class () {
            use SoftLimitTrait {
                memoryLimitReached as public;
            }

            protected function getMemoryLimit(): int
            {
                return 0;
            }
        };

        $this->assertFalse($instance->memoryLimitReached());
    }

    public function testMemoryLimitNotReachedWhenUsageIsLower(): void
    {
        $currentMemoryUsage = memory_get_usage(true);
        $instance = new class ($currentMemoryUsage + 1024 * 1024) { // 1MB higher than current usage
            use SoftLimitTrait {
                memoryLimitReached as public;
            }

            public function __construct(private int $limit)
            {
            }

            protected function getMemoryLimit(): int
            {
                return $this->limit;
            }
        };

        $this->assertFalse($instance->memoryLimitReached());
    }

    public function testMemoryLimitReachedWhenUsageIsHigher(): void
    {
        $currentMemoryUsage = memory_get_usage(true);
        $instance = new class ($currentMemoryUsage - 1024) { // 1KB lower than current usage
            use SoftLimitTrait {
                memoryLimitReached as public;
            }

            public function __construct(private int $limit)
            {
            }

            protected function getMemoryLimit(): int
            {
                return $this->limit;
            }
        };

        $this->assertTrue($instance->memoryLimitReached());
    }

    public function testMemoryLimitExceededWhenUsageIncreases(): void
    {
        $currentMemoryUsage = memory_get_usage(true);
        $instance = new class ($currentMemoryUsage + 5 * 1024 * 1024) { // Set limit 5MB higher than current usage
            use SoftLimitTrait {
                memoryLimitReached as public;
            }

            public function __construct(private int $limit)
            {
            }

            protected function getMemoryLimit(): int
            {
                return $this->limit;
            }
        };

        // Initially memory limit is not reached
        $this->assertFalse($instance->memoryLimitReached());

        // Create a large string to increase memory usage
        $largeString = str_repeat('x', 5 * 1024 * 1024 + 1); // 5MB and 1 byte string

        // Now memory limit should be exceeded
        $this->assertTrue($instance->memoryLimitReached());

        // Clean up to free memory
        unset($largeString);
    }
}
