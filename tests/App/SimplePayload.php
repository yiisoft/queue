<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Payload\PayloadInterface;

/**
 * Simple Payload.
 */
class SimplePayload implements PayloadInterface
{
    protected string $name = 'simple';

    public function getName(): string
    {
        return $this->name;
    }

    public function getData(): string
    {
        return '';
    }

    public function getMeta(): array
    {
        return [];
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
