<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use RuntimeException;

class ExceptionalSimpleJob extends SimpleJob
{
    public function execute(): void
    {
        parent::execute();
        throw new RuntimeException('Test exception');
    }
}
