<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Payload;

/**
 * Payload Interface.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
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
