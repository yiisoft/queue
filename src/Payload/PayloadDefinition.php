<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Payload;

final class PayloadDefinition
{
    private string $name;
    private $data;
    private ?array $properties;
    private ?string $class;

    public function __construct(string $name, $data, ?array $properties = [], ?string $class = '')
    {
        $this->name = $name;
        $this->data = $data;
        $this->properties = $properties;
        $this->class = $class;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProperties(): ?array
    {
        return $this->properties;
    }
}
