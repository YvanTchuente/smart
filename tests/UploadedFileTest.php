<?php

declare(strict_types=1);

namespace Tym\Smart\Tests;

use PHPUnit\Framework\TestCase;
use Tym\Smart\Http\Message\Stream;
use Psr\Http\Message\StreamInterface;
use Tym\Smart\Http\Message\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;

final class UploadedFileTest extends TestCase
{
    private UploadedFileInterface $uploadedFile;

    public static function setUpBeforeClass(): void
    {
        file_put_contents(__DIR__ . "/test.txt", "We are currently conducting unit tests.");
    }

    public function setUp(): void
    {
        $stream = new Stream(__DIR__ . "/test.txt");
        $this->uploadedFile = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'test.txt', 'text/xml');
    }

    public function testGetStream()
    {
        $this->assertInstanceOf(StreamInterface::class, $this->uploadedFile->getStream());
    }

    public function testMoveTo()
    {
        $this->assertTrue($this->uploadedFile->moveTo(dirname(__DIR__) . "/test.txt"));
    }

    public function testGetSize()
    {
        $this->assertSame(39, $this->uploadedFile->getSize());
    }

    public function testGetError()
    {
        $this->assertSame(UPLOAD_ERR_OK, $this->uploadedFile->getError());
    }

    public function testGetClientFilename()
    {
        $this->assertSame('test.txt', $this->uploadedFile->getClientFilename());
    }

    public function testGetClientMediaType()
    {
        $this->assertSame('text/xml', $this->uploadedFile->getClientMediaType());
    }

    public function tearDown(): void
    {
        if (file_exists(dirname(__DIR__) . "/test.txt")) {
            rename(dirname(__DIR__) . "/test.txt", __DIR__ . "/test.txt");
        }
    }

    public static function tearDownAfterClass(): void
    {
        file_put_contents(__DIR__ . "/test.txt", "");
    }
}
