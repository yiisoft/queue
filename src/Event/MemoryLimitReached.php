<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Event;

class MemoryLimitReached
{
    private int $limit;
    private int $actual;

    public function __construct(int $limit, int $actual)
    {
        $this->limit = $limit;
        $this->actual = $actual;
    }

    public function getActualUsage(): int
    {
        return $this->actual;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
