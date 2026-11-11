<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Cli;

use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

use function base64_encode;
use function fflush;
use function fwrite;
use function getenv;
use function implode;
use function in_array;
use function is_resource;
use function is_string;
use function preg_match;
use function preg_replace;
use function sprintf;
use function stream_isatty;
use function strlen;
use function strtolower;
use function substr;
use function trim;

/**
 * Reports the current program status to the terminal using the Program Status Protocol (OSC 7501).
 *
 * By default, a report is written only when the console output is decorated, not quiet, and backed by an
 * interactive TTY, so redirects, log files, cron runs and quiet invocations stay clean. Set the environment
 * variable `YII_QUEUE_STATUS` to `off` (also `0`, `no`, `false`) to disable the reports explicitly.
 *
 * @see https://www.superlogical.com/rex/docs/build/program-status
 */
final class TerminalStatusReporter
{
    private const ENV_VAR = 'YII_QUEUE_STATUS';
    private const MAX_MESSAGE_BYTES = 2048;
    private const MAX_ID_BYTES = 128;
    private const ID_PATTERN = '/^[A-Za-z0-9_.+-]{1,32}(?:\/[A-Za-z0-9_.+-]{1,32}){0,7}$/D';
    private const APP_PATTERN = '/^[A-Za-z0-9_.+-]{1,32}$/D';
    private const STATES = ['idle', 'working', 'done', 'error', 'blocked'];
    private const KINDS = ['permission', 'question', 'auth'];

    private bool $enabled;
    private string $app;
    /** @var resource|null */
    private $stream;

    /** @param resource|null $stream */
    private function __construct(bool $enabled, ?string $app, $stream = null)
    {
        if ($app === null) {
            $app = 'queue';
        }

        if (!preg_match(self::APP_PATTERN, $app)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The terminal status app "%s" must be 1 to 32 characters among letters, digits, "_", ".", "+" and "-".',
                    $app,
                ),
            );
        }

        $this->enabled = $enabled;
        $this->app = $app;
        $this->stream = $enabled && is_resource($stream) ? $stream : null;
    }

    /**
     * Creates a reporter for the given console output.
     *
     * By default, the report is enabled only when the output is backed by an interactive TTY stream, is decorated
     * and not quiet, and the `YII_QUEUE_STATUS` environment variable does not opt out.
     *
     * @param string|null $app A stable identifier reported to the terminal, 1 to 32 characters among letters,
     *     digits, "_", "." and "-"; defaults to `queue`.
     * @param bool|null   $enabled When non-null, forces the report on or off, bypassing TTY detection and the
     *     `YII_QUEUE_STATUS` opt-out (intended for tests or forced terminals).
     */
    public static function forOutput(OutputInterface $output, ?string $app = null, ?bool $enabled = null): self
    {
        $stream = $output instanceof StreamOutput ? $output->getStream() : null;

        if ($enabled === null) {
            $enabled = !self::isOptedOut()
                && $output->isDecorated()
                && !$output->isQuiet()
                && $stream !== null
                && stream_isatty($stream);
        }

        return new self($enabled, $app, $stream);
    }

    /**
     * Reports a program status update.
     *
     * @param string $state One of `idle`, `working`, `done`, `error`, `blocked`.
     * @param string|null $message A single human-readable line; control characters are replaced by spaces and long
     *     text is cut.
     * @param string|null $id The task the report is about; `null` reports the program itself. A "/" in an id makes
     *     the report a child of the parent task.
     * @param int|null $progress A percentage for a `working` or `blocked` report; `null` when indeterminate.
     * @param string|null $kind What a `blocked` report waits for: `permission`, `question` or `auth`.
     *
     * @throws InvalidArgumentException If the state, id, kind or progress is invalid.
     */
    public function emit(string $state, ?string $message = null, ?string $id = null, ?int $progress = null, ?string $kind = null): void
    {
        if (!$this->enabled) {
            return;
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

        if ($id !== null) {
            self::validateId($id);
        }

        $pairs = ['state=' . $state];
        if ($id !== null) {
            $pairs[] = 'id=' . $id;
        }
        if ($kind !== null) {
            $pairs[] = 'kind=' . $kind;
        }
        if ($progress !== null) {
            $pairs[] = 'progress=' . $progress;
        }
        $pairs[] = 'app=' . $this->app;

        $message = self::normalizeText($message);
        if ($message !== null) {
            $pairs[] = 'msg=' . base64_encode($message);
        }

        $this->write("\x1b]7501;" . implode(':', $pairs) . "\x1b\\");
    }

    /**
     * Removes the record of a task and of the records beneath it, or all records when no id is given.
     *
     * @param string|null $id The task record to remove.
     *
     * @throws InvalidArgumentException If the id is invalid.
     */
    public function clear(?string $id = null): void
    {
        if (!$this->enabled) {
            return;
        }

        if ($id !== null) {
            self::validateId($id);
        }

        $this->write($id === null ? "\x1b]7501;state=clear\x1b\\" : "\x1b]7501;state=clear:id={$id}\x1b\\");
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    private static function isOptedOut(): bool
    {
        $value = getenv(self::ENV_VAR);

        return is_string($value) && in_array(strtolower(trim($value)), ['off', '0', 'no', 'false'], true);
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

    /**
     * Terminals discard a whole report whose text holds a control character or exceeds the size limit, so the
     * text is made safe instead.
     *
     * Unlike the canonical encoder, this replacement does not require `ext-mbstring` or ANSI stripping, so it
     * additionally replaces unpaired UTF-16 surrogate sequences with spaces rather than scrubbing arbitrary
     * invalid UTF-8.
     */
    private static function normalizeText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = trim((string) preg_replace('/(?:[\x00-\x1F\x7F]|\xC2[\x80-\x9F]|\xED[\xA0-\xAF][\x80-\xBF])+/', ' ', $text));

        if (strlen($text) > self::MAX_MESSAGE_BYTES) {
            $text = substr($text, 0, self::MAX_MESSAGE_BYTES);
            while (strlen($text) > 0 && !preg_match('//u', $text)) {
                $text = substr($text, 0, -1);
            }
            $text = rtrim($text);
        }

        return $text !== '' ? $text : null;
    }

    private function write(string $sequence): void
    {
        if ($this->stream === null) {
            return;
        }

        fwrite($this->stream, $sequence);
        fflush($this->stream);
    }
}
