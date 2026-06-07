<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Queue\Message\Envelope;
use Yiisoft\Queue\Message\MessageInterface;

use function array_key_exists;
use function is_array;

/**
 * @extends Envelope<array{
 *      yii-failure: array<string, scalar|null|array<scalar|null|array>>,
 *      ...<string, scalar|null|array<scalar|null|array>>
 *  }>
 */
final class FailureEnvelope extends Envelope
{
    public const META_FAILURE = 'yii-failure';

    private array $failureMetadata;

    public function __construct(MessageInterface $message, array $failureMetadata = [])
    {
        $this->failureMetadata = $failureMetadata === []
            ? self::getFailureMetadataFromMessage($message)
            : ArrayHelper::merge(
                self::getFailureMetadataFromMessage($message),
                $failureMetadata,
            );
        parent::__construct($message, [
            self::META_FAILURE => $this->failureMetadata,
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
        if (array_key_exists(self::META_FAILURE, $metadata)) {
            $result = $metadata[self::META_FAILURE];
            return is_array($result) ? $result : [];
        }
        return [];
    }
}
