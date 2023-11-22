<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ConsoleQueueTest extends TestCase
{
    public function testQueueRunsSuccessfully(): void
    {
        $output = shell_exec('php ./bin/queue');

        $this->assertStringContainsString('Yii Queue Tool 1.0.0', $output);
    }
}
