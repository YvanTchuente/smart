<?php

declare(strict_types=1);

namespace Tym\Smart\Tests;

use PHPUnit\Framework\TestCase;
use Tym\Smart\Http\Message\Stream;
use Psr\Http\Message\StreamInterface;

final class StreamTest extends TestCase
{
    private StreamInterface $stream;

    public static function setUpBeforeClass(): void
    {
        file_put_contents(__DIR__ . "/test.txt", "We are currently conducting unit tests.");
    }

    public function setUp(): void
    {
        $this->stream = new Stream(__DIR__ . "/test.txt");
    }

    public function testClose()
    {
        $this->stream->close();
        $this->assertNull($this->stream->detach());
    }

    public function testDetach()
    {
        $stream = $this->stream->detach();
        $this->assertIsResource($stream);
        $this->assertNull($this->stream->getSize());
        $this->assertFalse($this->stream->isReadable());
        $this->assertFalse($this->stream->isWritable());
        $this->assertFalse($this->stream->isSeekable());
        $this->assertSame([], $this->stream->getMetadata());
    }

    public function testGetSize()
    {
        $this->assertSame(39, $this->stream->getSize());
    }

    public function testTell()
    {
        $this->assertSame(0, $this->stream->tell());
    }

    public function testEof()
    {
        $this->assertFalse($this->stream->eof());
    }

    public function testIsSeekable()
    {
        $this->assertTrue($this->stream->isSeekable());
    }

    public function testSeek()
    {
        $this->assertSame(0, $this->stream->seek(0));
    }

    public function testRewind()
    {
        $this->stream->rewind();
        $this->assertSame(0, $this->stream->tell());
    }

    public function testGetContents()
    {
        $contents = $this->stream->getContents();
        $expected = "We are currently conducting unit tests.";
        $this->assertSame($expected, $contents);
    }

    public function testIsReadable()
    {
        $this->assertTrue($this->stream->isWritable());
    }

    public function testRead()
    {
        $value = $this->stream->read(16);
        $this->assertSame("We are currently", $value);
    }

    public function testIsWritable()
    {
        $this->assertTrue($this->stream->isWritable());
    }

    public function testWrite()
    {
        $this->stream->seek(0, SEEK_END);
        $length = $this->stream->write("integration");
        $this->assertSame(11, $length);
    }

    public function testGetMetadata()
    {
        $this->assertNotEmpty($this->stream->getMetadata());
    }

    public static function tearDownAfterClass(): void
    {
        file_put_contents(__DIR__ . "/test.txt", "");
    }
}
