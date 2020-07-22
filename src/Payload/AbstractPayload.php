<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Payload;

abstract class AbstractPayload implements PayloadInterface
{
    public function getName(): string
    {
        return static::class;
    }

    public function getMeta(): array
    {
        return [];
    }
}
