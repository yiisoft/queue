<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception\AdapterConfiguration;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class EmptyDefaultAdapterException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    protected $message = 'Default queue adapter is not set';

    public function getName(): string
    {
        return 'Empty default adapter';
    }

    public function getSolution(): ?string
    {
        // TODO: Implement getSolution() method.
    }
}
