<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

interface ParametrizedMessageInterface extends MessageInterface
{
    public function setId(?string $id): void;

    /**
     * Returns unique message ID.
     *
     * @return string|null
     */
    public function getId(): ?string;
}
