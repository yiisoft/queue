<?php

namespace Yiisoft\Yii\Queue\Payload;

interface PayloadInterface
{
    public const META_KEY_PRIORITY = 'priority';
    public const META_KEY_DELAY = 'delay';
    public const META_KEY_ATTEMPTS = 'attempts';
    public const DEFAULT_META_KEYS = [self::META_KEY_PRIORITY, self::META_KEY_DELAY, self::META_KEY_ATTEMPTS];

    public function getName(): string;
    public function getData();
    public function getMeta(): array;
}
