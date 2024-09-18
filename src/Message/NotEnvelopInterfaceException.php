<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

use InvalidArgumentException;
use Throwable;

final class NotEnvelopInterfaceException extends InvalidArgumentException
{
    public function __construct(string $className = '', int $code = 0, ?Throwable $previous = null)
    {
        $message = sprintf(
            'The given class "%s" does not implement "%s".',
            $className,
            EnvelopeInterface::class
        );

        parent::__construct($message, $code, $previous);
    }
}
