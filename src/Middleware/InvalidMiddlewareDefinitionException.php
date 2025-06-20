<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

use InvalidArgumentException;
use Throwable;

use function is_array;
use function is_object;
use function is_string;

final class InvalidMiddlewareDefinitionException extends InvalidArgumentException
{
    /**
     * @param array|callable|string $middlewareDefinition
     */
    public function __construct($middlewareDefinition, int $code = 0, ?Throwable $previous = null)
    {
        $message = 'Parameter should be either middleware class name or a callable.';

        $definitionString = $this->convertDefinitionToString($middlewareDefinition);
        if ($definitionString !== null) {
            $message .= ' Got ' . $definitionString . '.';
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @param mixed $middlewareDefinition Middleware definition.
     * @return string|null
     */
    private function convertDefinitionToString(mixed $middlewareDefinition): ?string
    {
        if (is_object($middlewareDefinition)) {
            return 'an instance of "' . $middlewareDefinition::class . '"';
        }

        if (is_string($middlewareDefinition)) {
            return '"' . $middlewareDefinition . '"';
        }

        if (is_array($middlewareDefinition)) {
            $items = $middlewareDefinition;
            foreach ($middlewareDefinition as $item) {
                if (!is_string($item)) {
                    return null;
                }
            }
            array_walk(
                $items,
                static function (mixed &$item, int|string $key) {
                    $item = (string) $item;
                    $item = '"' . $item . '"';
                    if (is_string($key)) {
                        $item = '"' . $key . '" => ' . $item;
                    }
                }
            );

            /** @var string[] $items */
            return '[' . implode(', ', $items) . ']';
        }

        return null;
    }
}
