<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Queue\Message\Envelope;
use Yiisoft\Queue\Message\MessageInterface;

use function array_key_exists;
use function is_array;

final class FailureEnvelope extends Envelope
{
    public const META_FAILURE_METADATA = 'yii-failure-metadata';

    private array $failureMetadata;

    public function __construct(MessageInterface $message, array $failureMetadata = [])
    {
        $this->failureMetadata = ArrayHelper::merge(
            self::getFailureMetadataFromMessage($message),
            $failureMetadata,
        );
        parent::__construct($message, [
            self::META_FAILURE_METADATA => $this->failureMetadata,
        ]);
    }

    public function getFailureMetadata(): array
    {
        return $this->failureMetadata;
    }

    public function getFailureMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->failureMetadata[$key] ?? $default;
    }

    public static function fromMessage(MessageInterface $message): static
    {
        return new self(
            $message,
            self::getFailureMetadataFromMessage($message),
        );
    }

    private static function getFailureMetadataFromMessage(MessageInterface $message): array
    {
        $metadata = $message->getMetadata();
        if (array_key_exists(self::META_FAILURE_METADATA, $metadata)) {
            $result = $metadata[self::META_FAILURE_METADATA];
            return is_array($result) ? $result : [];
        }
        return [];
    }
}
