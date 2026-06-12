<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message\Serializer;

use RuntimeException;

/**
 * Thrown when message encoding/decoding fails, or when a decoded message has an invalid format.
 */
final class MessageEncoderException extends RuntimeException {}
