<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception\DriverConfiguration;

use RuntimeException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class DriverNotConfiguredException extends RuntimeException implements FriendlyExceptionInterface
{
    protected $message = 'Queue driver in not set';

    public function getName(): string
    {
        return 'Driver not configured';
    }

    public function getSolution(): ?string
    {
        // TODO: Implement getSolution() method.
    }
}
