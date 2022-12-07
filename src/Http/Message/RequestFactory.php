<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Message;

use Psr\Http\Message\UriInterface;
use Tym\Smart\Http\Message\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class RequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        if (empty($method)) {
            throw new \InvalidArgumentException("Invalid method");
        }
        if (!is_string($uri) && !($uri instanceof UriInterface)) {
            throw new \InvalidArgumentException("Invalid uri");
        }
        $request = new Request($method, $uri);
        return $request;
    }
}
