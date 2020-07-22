<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Yiisoft\Yii\Queue\Payload\PayloadInterface;

interface MessageInterface
{
    /**
     * Returns unique message id
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Returns a job to execute
     *
     * @return PayloadInterface
     */
    public function getPayload(): PayloadInterface;
}
