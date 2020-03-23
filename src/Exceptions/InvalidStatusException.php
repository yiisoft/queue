<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exceptions;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class InvalidStatusException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    protected $message = 'Invalid status provided';

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'Invalid status provided';
    }

    /**
     * @inheritDoc
     */
    public function getSolution(): ?string
    {
        return 'Refer to \Yiisoft\Yii\Queue\Enum\JobStatus::available() to see available status list.';
    }
}
