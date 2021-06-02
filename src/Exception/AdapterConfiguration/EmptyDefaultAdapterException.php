<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception\AdapterConfiguration;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Queue\QueueFactory;

class EmptyDefaultAdapterException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    protected $message = 'Default queue adapter is not set';

    public function getName(): string
    {
        return 'Empty default adapter';
    }

    public function getSolution(): ?string
    {
        $factoryClass = QueueFactory::class;

        return <<<SOLUTION
            $factoryClass::defaultAdapter property must be set to allow runtime adapter generation without
            explicit channel definition. Please refer to the $factoryClass::__constructor() documentation,
            parameters "\$enableRuntimeChannelDefinition" and "\$defaultAdapter".
            SOLUTION;
    }
}
