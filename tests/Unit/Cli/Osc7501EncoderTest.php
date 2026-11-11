<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Cli;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Cli\Osc7501Encoder;

use function strlen;

final class Osc7501EncoderTest extends TestCase
{
    public function testBlockedStatusCarriesSafeMessageAndProgress(): void
    {
        self::assertSame(
            "\x1b]7501;state=blocked:id=queue/run:kind=permission:progress=50:app=queue:msg=" . base64_encode('a b c') . "\x1b\\",
            Osc7501Encoder::encode('blocked', 'queue/run', 'queue', "a\n\tb\xC2\x80c", 50, 'permission'),
        );
    }

    #[DataProvider('invalidText')]
    public function testInvalidUtf8IsOmittedWithoutLosingTheState(string $text): void
    {
        self::assertSame("\x1b]7501;state=working:id=queue/run:app=queue\x1b\\", Osc7501Encoder::encode('working', 'queue/run', 'queue', $text));
    }

    public static function invalidText(): array
    {
        return [["bad\xFF"], ["a\xED\xA0\x80b"], ["a\xED\xB0\x80b"], ["\xC0\xAF"], ["\xF4\x90\x80\x80"]];
    }

    public function testLongTextIsCutOnUtf8BoundaryWithinTheWholeReportLimit(): void
    {
        $text = str_repeat('a', 2047) . '€';
        $sequence = Osc7501Encoder::encode('working', 'queue/run', 'queue', $text);
        self::assertStringContainsString('msg=' . base64_encode(str_repeat('a', 2047)), $sequence);
        self::assertLessThanOrEqual(4096, strlen($sequence));
    }

    public function testEmptyMessageIsOmitted(): void
    {
        self::assertStringNotContainsString('msg=', Osc7501Encoder::encode('done', 'queue/run', 'queue', " \n "));
    }

    public function testUnicodeSeparatorsBecomeSpacesInTheDecodedMessage(): void
    {
        $sequence = Osc7501Encoder::encode('working', 'queue/run', 'queue', "mail\u{2028}news\u{2029}jobs");
        preg_match('/msg=([A-Za-z0-9+\/=]+)/', $sequence, $matches);

        self::assertSame('mail news jobs', base64_decode($matches[1], true));
    }

    #[DataProvider('invalidFields')]
    public function testInvalidFieldsAreRejected(string $state, string $id, string $app, ?int $progress, ?string $kind): void
    {
        $this->expectException(InvalidArgumentException::class);
        Osc7501Encoder::encode($state, $id, $app, progress: $progress, kind: $kind);
    }

    public static function invalidFields(): array
    {
        return [
            ['unknown', 'queue/run', 'queue', null, null],
            ['done', 'queue/run', 'queue', 20, null],
            ['working', 'queue/run', 'queue', -1, null],
            ['working', 'queue/run', 'queue', 101, null],
            ['working', 'queue/run', 'queue', null, 'auth'],
            ['blocked', 'queue/run', 'queue', null, 'unknown'],
            ['working', '', 'queue', null, null],
            ['working', 'a//b', 'queue', null, null],
            ['working', 'a:b', 'queue', null, null],
            ['working', str_repeat('a', 33), 'queue', null, null],
            ['working', implode('/', array_fill(0, 9, 'a')), 'queue', null, null],
            ['working', implode('/', array_fill(0, 5, str_repeat('a', 32))), 'queue', null, null],
            ['working', 'queue/run', 'bad:app', null, null],
        ];
    }

    public function testClearIsScopedToAnExplicitId(): void
    {
        self::assertSame("\x1b]7501;state=clear:id=queue/run\x1b\\", Osc7501Encoder::encode('clear', 'queue/run', 'queue'));
    }
}
