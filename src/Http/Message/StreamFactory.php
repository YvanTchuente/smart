<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Message;

use Tym\Smart\Http\Message\Stream;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        $stream = new Stream($content, ['isText' => true]);
        return $stream;
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        switch (true) {
            case (!is_resource($resource)):
                throw new \InvalidArgumentException("Invalid resource.");
                break;
            case (!preg_match("/(r+?|w+|a+|x+|c+)(b|t)?/", stream_get_meta_data($resource)['mode'])):
                throw new \RuntimeException("The resource must be readable.");
                break;
        }
        $stream = new Stream($resource);
        return $stream;
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $stream = new Stream($filename, ['mode' => $mode]);
        return $stream;
    }
}
