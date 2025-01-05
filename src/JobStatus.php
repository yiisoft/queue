<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

enum JobStatus: int
{
    case WAITING = 1;
    case RESERVED = 2;
    case DONE = 3;

    public function key(): string
    {
        return match ($this) {
            self::WAITING => 'waiting',
            self::RESERVED => 'reserved',
            self::DONE => 'done',
        };
    }
}
