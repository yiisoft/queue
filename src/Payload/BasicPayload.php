<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Payload;

class BasicPayload implements PayloadInterface
{
    protected string $name;
    protected $data;
    protected array $meta;

    public function __construct(string $name, $data, array $meta)
    {
        $this->name = $name;
        $this->data = $data;
        $this->meta = $meta;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
}
