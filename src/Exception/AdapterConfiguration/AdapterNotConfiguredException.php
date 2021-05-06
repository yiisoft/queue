<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception\AdapterConfiguration;

use RuntimeException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class AdapterNotConfiguredException extends RuntimeException implements FriendlyExceptionInterface
{
    protected $message = 'Queue adapter is not configured';

    public function getName(): string
    {
        return 'Adapter is not configured';
    }

    public function getSolution(): ?string
    {
        // TODO: Implement getSolution() method.
    }
}
