<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Tests\Transport\Serialization;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Tests\Fixtures\DummyMessage;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

class PhpSerializerTest extends TestCase
{
    public function testEncodedIsDecodable(): void
    {
        $serializer = new PhpSerializer();

        $envelope = new Envelope(new DummyMessage('Hello'));

        $encoded = $serializer->encode($envelope);
        $this->assertStringNotContainsString("\0", $encoded['body'], 'Does not contain the binary characters');
        $this->assertEquals($envelope, $serializer->decode($encoded));
    }

    public function testDecodingFailsWithMissingBodyKey(): void
    {
        $this->expectException(MessageDecodingFailedException::class);
        $this->expectExceptionMessage('Encoded envelope should have at least a "body".');

        $serializer = new PhpSerializer();

        $serializer->decode([]);
    }

    public function testDecodingFailsWithBadFormat(): void
    {
        $this->expectException(MessageDecodingFailedException::class);
        $this->expectExceptionMessageMatches('/Could not decode/');

        $serializer = new PhpSerializer();

        $serializer->decode([
            'body' => '{"message": "bar"}',
        ]);
    }

    public function testDecodingFailsWithBadClass(): void
    {
        $this->expectException(MessageDecodingFailedException::class);
        $this->expectExceptionMessageMatches('/class "ReceivedSt0mp" not found/');

        $serializer = new PhpSerializer();

        $serializer->decode([
            'body' => 'O:13:"ReceivedSt0mp":0:{}',
        ]);
    }

    public function testEncodedSkipsNonEncodeableStamps(): void
    {
        $serializer = new PhpSerializer();

        $envelope = new Envelope(new DummyMessage('Hello'), [
            new DummyPhpSerializerNonSendableStamp(),
        ]);

        $encoded = $serializer->encode($envelope);
        $this->assertStringNotContainsString('DummyPhpSerializerNonSendableStamp', $encoded['body']);
    }
}

class DummyPhpSerializerNonSendableStamp implements NonSendableStampInterface
{
}
