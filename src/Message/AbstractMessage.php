<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

abstract class AbstractMessage implements MessageInterface
{
    protected ?string $id = null;


}
