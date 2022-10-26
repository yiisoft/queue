<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

interface MessageInterface
{
    public function setId(?string $id): void;

    /**
     * Returns unique message ID.
     *
     * @return string|null
     */
    public function getId(): ?string;

    /**
     * Returns handler name.
     *
     * @return string
     */
    public function getHandlerName(): string;

    /**
     * Returns payload data.
     *
     * @return mixed
     */
    public function getData();
}
