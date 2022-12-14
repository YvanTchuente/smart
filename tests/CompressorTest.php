<?php

declare(strict_types=1);

namespace Tym\Smart\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Tym\Smart\Http\Message\Compressor;
use Tym\Smart\Http\Message\StreamFactory;
use Tym\Smart\Http\Message\RequestFactory;

final class CompressorTest extends TestCase
{
    private Compressor $compressor;

    protected function setUp(): void
    {
        $this->compressor = new Compressor;
    }

    public function testCompress()
    {
        $message = (new RequestFactory)->createRequest('GET', '/')->withBody((new StreamFactory())->createStream("We're testing"));
        $size = $message->getBody()->getSize();

        $message = $this->compressor->compress($message);

        $this->assertLessThan($message->getBody()->getSize(), $size);

        return $message;
    }

    /**
     * @depends testCompress
     */
    public function testDecompress(MessageInterface $message)
    {
        $size = $message->getBody()->getSize();

        $message = $this->compressor->decompress($message);

        $this->assertGreaterThan($message->getBody()->getSize(), $size);
    }
}
