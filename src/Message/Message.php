<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

final class Message implements MessageInterface
{
    use BehaviorTrait;

    private ?string $id = null;
    private string $name;
    private $data;

    public function __construct(string $name, $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData()
    {
        return $this->data;
    }
}
