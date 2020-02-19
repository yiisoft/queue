<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Enum;

use ReflectionClass;

abstract class AbstractEnum
{
    protected array $cache;

    public function __construct()
    {
        $result = [];

        $reflectionClass = new ReflectionClass(static::class);
        foreach ($reflectionClass->getReflectionConstants() as $reflectionConstant) {
            if ($reflectionConstant->isPublic()) {
                $result[$reflectionConstant->getName()] = $reflectionConstant->getValue();
            }
        }

        $this->cache = $result;
    }

    /**
     * Returns array of public constants with values
     *
     * @return array Array like `[constant_name => value]`
     */
    public function getValues(): array
    {
        return $this->cache;
    }
}
