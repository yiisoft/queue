<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

interface MessageInterface
{
    public function setId(?string $id): void;

    /**
     * Returns unique message id
     *
     * @return string
     */
    public function getId(): ?string;

    /**
     * Returns payload name
     *
     * @return string
     */
    public function getPayloadName(): string;

    /**
     * Returns data for job payload
     *
     * @return mixed
     */
    public function getPayloadData();

    /**
     * Returns metadata for job payload
     *
     * @return array
     */
    public function getPayloadMeta(): array;
}
