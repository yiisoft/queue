<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Payload\PayloadInterface;

/**
 * Simple Payload.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class SimplePayload implements PayloadInterface
{
    public bool $executed = false;

    public function getName(): string
    {
        return 'simple';
    }

    public function getData(): string
    {
        return '';
    }

    public function getMeta(): array
    {
        return [];
    }
}
