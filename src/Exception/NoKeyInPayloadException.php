<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Exception;

use InvalidArgumentException;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Queue\Message\MessageSerializerInterface;

final class NoKeyInPayloadException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    public function __construct(
        protected string $expectedKey,
        protected array $payload,
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(
            sprintf(
                'No expected key "%s" in payload. Payload key list: "%s".',
                $expectedKey,
                implode('", "', array_keys($payload))
            ),
            $code,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return sprintf('No key "%s" in payload', $this->expectedKey);
    }

    /**
     * @return string
     *
     * @infection-ignore-all
     */
    public function getSolution(): ?string
    {
        return sprintf(
            "We have successfully unserialized a message, but there was no expected key \"%s\".
        There are the following keys in the message: %s.
        You might want to change message's structure, or make your own implementation of %s,
        which won't rely on this key in the message.",
            $this->expectedKey,
            implode('", "', array_keys($this->payload)),
            MessageSerializerInterface::class
        );
    }
}
