<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Message\Serializer;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\Serializer\JsonMessageEncoder;
use Yiisoft\Queue\Message\Serializer\MessageEncoderException;

final class JsonMessageEncoderTest extends TestCase
{
    #[TestWith([[], '[]'])]
    #[TestWith([['type' => 'test', 'data' => 'value', 'meta' => []], '{"type":"test","data":"value","meta":[]}'])]
    #[TestWith([['num' => 42, 'flag' => true, 'nothing' => null], '{"num":42,"flag":true,"nothing":null}'])]
    #[TestWith([['nested' => ['a' => 1, 'b' => 'str']], '{"nested":{"a":1,"b":"str"}}'])]
    public function testEncode(array $data, string $expected): void
    {
        $this->assertSame($expected, (new JsonMessageEncoder())->encode($data));
    }

    #[TestWith(['[]', []])]
    #[TestWith(['{"type":"test","data":"value","meta":[]}', ['type' => 'test', 'data' => 'value', 'meta' => []]])]
    #[TestWith(['{"num":42,"flag":true,"nothing":null}', ['num' => 42, 'flag' => true, 'nothing' => null]])]
    #[TestWith(['{"nested":{"a":1,"b":"str"}}', ['nested' => ['a' => 1, 'b' => 'str']]])]
    #[TestWith(['"string"', 'string'])]
    #[TestWith(['42', 42])]
    #[TestWith(['true', true])]
    #[TestWith(['null', null])]
    public function testDecode(string $json, mixed $expected): void
    {
        $this->assertSame($expected, (new JsonMessageEncoder())->decode($json));
    }

    public function testDecodeInvalidJson(): void
    {
        $this->expectException(MessageEncoderException::class);
        (new JsonMessageEncoder())->decode('{invalid}');
    }
}
