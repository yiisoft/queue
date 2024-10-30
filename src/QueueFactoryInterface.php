<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use Yiisoft\Definitions\Exception\InvalidConfigException;

interface QueueFactoryInterface
{
    /** @psalm-suppress MissingClassConstType */
    public const DEFAULT_CHANNEL_NAME = 'yii-queue';

    /**
     * @throws InvalidConfigException
     */
    public function get(string $channel = self::DEFAULT_CHANNEL_NAME): QueueInterface;
}
