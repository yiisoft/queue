<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

trait ParametrizedMessageTrait
{
    protected ?string $id = null;

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * Returns unique message ID.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }
}
