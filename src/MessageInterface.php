<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

interface MessageInterface
{
    public function setId(?string $id): void;

    /**
     * Returns unique message id
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Returns a job to execute
     *
     * @return string
     */
    public function getPayloadName(): string;

    public function getPayloadData();

    public function getPayloadMeta(): array;
}
