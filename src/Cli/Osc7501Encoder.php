<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Cli;

use InvalidArgumentException;

use function base64_encode;
use function implode;
use function in_array;
use function preg_match;
use function preg_replace;
use function sprintf;
use function strlen;
use function substr;
use function trim;

/** @internal Encodes OSC 7501 without depending on a console framework or output stream. */
final class Osc7501Encoder
{
    private const MAX_MESSAGE_BYTES = 2048;
    private const MAX_ID_BYTES = 128;
    private const ID_PATTERN = '/^[A-Za-z0-9_.+-]{1,32}(?:\/[A-Za-z0-9_.+-]{1,32}){0,7}$/D';
    private const APP_PATTERN = '/^[A-Za-z0-9_.+-]{1,32}$/D';
    private const STATES = ['idle', 'working', 'done', 'error', 'blocked'];
    private const KINDS = ['permission', 'question', 'auth'];

    public static function encode(
        string $state,
        string $id,
        string $app,
        ?string $message = null,
        ?int $progress = null,
        ?string $kind = null,
    ): string {
        self::validateId($id);
        if (!preg_match(self::APP_PATTERN, $app)) {
            throw new InvalidArgumentException('The terminal status app must be 1 to 32 ASCII identifier characters.');
        }
        if ($state === 'clear') {
            return "\x1b]7501;state=clear:id={$id}\x1b\\";
        }

        if (!in_array($state, self::STATES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The terminal status state "%s" must be one of: %s.',
                    $state,
                    implode(', ', self::STATES),
                ),
            );
        }

        if ($kind !== null) {
            if ($state !== 'blocked') {
                throw new InvalidArgumentException(
                    sprintf(
                        'A terminal status kind is only supported for the "blocked" state, "%s" given.',
                        $state,
                    ),
                );
            }

            if (!in_array($kind, self::KINDS, true)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'The terminal status kind "%s" must be one of: %s.',
                        $kind,
                        implode(', ', self::KINDS),
                    ),
                );
            }
        }

        if ($progress !== null) {
            if ($state !== 'working' && $state !== 'blocked') {
                throw new InvalidArgumentException(
                    sprintf(
                        'A terminal status progress is only supported for the "working" and "blocked" states, "%s" given.',
                        $state,
                    ),
                );
            }

            if ($progress < 0 || $progress > 100) {
                throw new InvalidArgumentException(
                    sprintf('The terminal status progress must be between 0 and 100, %d given.', $progress),
                );
            }
        }

        $pairs = ['state=' . $state, 'id=' . $id];
        if ($kind !== null) {
            $pairs[] = 'kind=' . $kind;
        }
        if ($progress !== null) {
            $pairs[] = 'progress=' . $progress;
        }
        $pairs[] = 'app=' . $app;

        $message = self::normalizeText($message);
        if ($message !== null) {
            $pairs[] = 'msg=' . base64_encode($message);
        }

        return "\x1b]7501;" . implode(':', $pairs) . "\x1b\\";
    }

    private static function validateId(string $id): void
    {
        if (strlen($id) > self::MAX_ID_BYTES || !preg_match(self::ID_PATTERN, $id)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The terminal status id "%s" must be up to 8 "/"-separated segments of 1 to 32 characters '
                    . 'among letters, digits, "_", ".", "+" and "-", and at most %d bytes.',
                    $id,
                    self::MAX_ID_BYTES,
                ),
            );
        }
    }

    private static function normalizeText(?string $text): ?string
    {
        if ($text === null || !preg_match('//u', $text)) {
            return null;
        }

        // Bound work before sanitizing. Remove at most three bytes from a split UTF-8 character.
        $text = substr($text, 0, self::MAX_MESSAGE_BYTES);
        while ($text !== '' && !preg_match('//u', $text)) {
            $text = substr($text, 0, -1);
        }
        $text = trim((string) preg_replace('/[\x00-\x1F\x7F\x{80}-\x{9F}\x{2028}\x{2029}]+/u', ' ', $text));

        return $text !== '' ? $text : null;
    }
}
