<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

interface MessageInterface
{
    /**
     * Returns handler name.
     *
     * @return string
     */
    public function getHandlerName(): string;

    /**
     * Returns payload data.
     *
     * @return mixed
     */
    public function getData(): mixed;

    /**
     * Returns message metadata: timings, attempts count, metrics, etc.
     *
     * @return array
     */
    public function getMetadata(): array;
}
