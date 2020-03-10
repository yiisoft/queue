<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Enum;

use InvalidArgumentException;

class JobStatus
{
    public const WAITING = 1;
    public const RESERVED = 2;
    public const DONE = 3;

    protected int $status;

    protected function __construct(int $status)
    {
        if (!in_array($status, $this->available(), true)) {
            throw new InvalidArgumentException('Invalid status provided');
        }

        $this->status = $status;
    }

    protected function available(): array
    {
        return [self::WAITING, self::RESERVED, self::DONE];
    }

    public static function waiting(): self
    {
        return new static(self::WAITING);
    }

    public static function reserved(): self
    {
        return new static(self::RESERVED);
    }

    public static function done(): self
    {
        return new static(self::DONE);
    }

    public function isWaiting(): bool
    {
        return $this->status === self::WAITING;
    }

    public function isReserved(): bool
    {
        return $this->status === self::RESERVED;
    }

    public function isDone(): bool
    {
        return $this->status === self::DONE;
    }
}
