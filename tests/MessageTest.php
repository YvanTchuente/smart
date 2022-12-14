<?php

declare(strict_types=1);

namespace Tym\Smart\Tests;

use PHPUnit\Framework\TestCase;
use Tym\Smart\Http\Message\Stream;
use Tym\Smart\Http\Message\Request;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\MessageInterface;

final class MessageTest extends TestCase
{
    private MessageInterface $message;

    public static function setUpBeforeClass(): void
    {
        file_put_contents(__DIR__ . "/test.txt", "We are currently conducting unit tests.");
    }

    public function setUp(): void
    {
        $this->message = (new Request('GET', 'http://localhost:8000/register', 'HTTP/2.0'))
            ->withHeader('Accept-Encoding', ['gzip', 'compress'])
            ->withHeader('Content-Encoding', 'gzip')
            ->withHeader('Accept', ['en-gb', 'fr'])
            ->withBody(new Stream(__DIR__ . "/test.txt"));
    }

    public function testGetProtocolVersion()
    {
        $this->assertSame('2.0', $this->message->getProtocolVersion());
    }

    public function testWithProtocolVersion()
    {
        $message = $this->message->withProtocolVersion('HTTP/1.1');
        $this->assertSame('1.1', $message->getProtocolVersion());
    }

    public function testGetHeaders()
    {
        $headers = $this->message->getHeaders();
        $this->assertArrayHasKey('Content-Encoding', $headers);
        $this->assertArrayHasKey('Accept', $headers);
    }

    public function testHasHeader()
    {
        $this->assertTrue($this->message->hasHeader('Accept'));
        $this->assertFalse($this->message->hasHeader('Content-Type'));
    }

    public function testGetHeader()
    {
        $this->assertSame(['en-gb', 'fr'], $this->message->getHeader('Accept'));
    }

    public function testGetHeaderLine()
    {
        $this->assertSame('gzip', $this->message->getHeaderLine('Content-Encoding'));
    }

    public function testWithHeader()
    {
        $message = $this->message->withHeader('Accept-Encoding', ['compress', 'gzip']);
        $this->assertSame('compress,gzip', $message->getHeaderLine('Accept-Encoding'));
    }

    public function testWithAddedHeader()
    {
        $message = $this->message->withAddedHeader('Accept-Encoding', 'identity');
        $this->assertSame(['gzip', 'compress', 'identity'], $message->getHeader('Accept-Encoding'));
    }

    public function testWithoutHeader()
    {
        $message = $this->message->withoutHeader('Content-Encoding');
        $this->assertFalse($message->hasHeader('Content-Encoding'));
    }

    public function testGetBody()
    {
        $this->assertInstanceOf(StreamInterface::class, $this->message->getBody());
        $this->assertSame(39, $this->message->getBody()->getSize());
    }

    public function testWithBody()
    {
        file_put_contents(__DIR__ . "/test2.txt", "Running tests.");

        $stream = new Stream(__DIR__ . "/test2.txt");
        $message = $this->message->withBody($stream);

        $this->assertGreaterThan($message->getBody()->getSize(), $this->message->getBody()->getSize());
        unlink(__DIR__ . "/test2.txt");
    }

    public static function tearDownAfterClass(): void
    {
        file_put_contents(__DIR__ . "/test.txt", "");
    }
}
