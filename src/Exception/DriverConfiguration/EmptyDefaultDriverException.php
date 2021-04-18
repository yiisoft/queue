<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception\DriverConfiguration;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class EmptyDefaultDriverException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    protected $message = 'Default queue driver is not set';

    public function getName(): string
    {
        return 'Empty default driver';
    }

    public function getSolution(): ?string
    {
        // TODO: Implement getSolution() method.
    }
}
