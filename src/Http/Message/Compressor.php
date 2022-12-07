<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * HTTP message compressor.
 * 
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class Compressor
{
    protected StreamFactoryInterface $streamFactory;

    public function __construct()
    {
        $this->streamFactory = new StreamFactory;
    }

    /**
     * Compresses a HTTP message.
     * 
     * Performs a gzip encoding on the message's body and then modifies the headers 
     * to ensure it remains internally consistent.
     * 
     * @param MessageInterface $message The message to compress.
     *
     * @return MessageInterface
     */
    public function compress(MessageInterface $message)
    {
        // Retrieve and gzip compress the message's body contents
        $contents = (string) $message->getBody();
        $encoded_contents = gzencode($contents, 9);

        // Replace the message's body contents with its compressed version
        $tmp_file = tmpfile();
        fwrite($tmp_file, $encoded_contents);
        $encoded_body = $this->streamFactory->createStreamFromResource($tmp_file);
        $message = $message->withBody($encoded_body);

        // Appropriately modify the headers
        $size = $message->getBody()->getSize();
        $message = $message->withHeader('Content-Encoding', 'gzip')->withHeader('Content-Length', (string) $size);

        return $message;
    }

    /**
     * Decompresses a HTTP message.
     * 
     * Performs a gzip decoding on the message's body and then modifies the headers 
     * to ensure it remains internally consistent.
     * 
     * @param MessageInterface $message The message to decompress.
     *
     * @return MessageInterface
     */
    public function decompress(MessageInterface $message)
    {
        // Retrieve and gzip decompress the message's body contents
        $contents = (string) $message->getBody();
        $decoded_contents = gzdecode($contents);

        // Replace the message's body contents with its decompressed version
        $stream = $message->getBody()->detach();
        ftruncate($stream, 0);
        fwrite($stream, $decoded_contents);
        $encoded_body = $this->streamFactory->createStreamFromResource($stream);
        $message = $message->withBody($encoded_body);

        // Appropriately modify the headers
        $size = $message->getBody()->getSize();
        $message = $message->withoutHeader('Content-Encoding')->withHeader('Content-Length', (string) $size);

        return $message;
    }

    public function setStreamFactory(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;

        return $this;
    }
}
