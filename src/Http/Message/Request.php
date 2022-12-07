<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Message;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class Request extends Message implements RequestInterface
{
    /**
     * Valid HTTP methods.
     */
    protected const VALID_METHODS = '/GET|HEAD|POST|PUT|DELETE|TRACE|CONNECT|OPTIONS/i';

    /**
     * The request URI.
     */
    protected ?UriInterface $uri;

    /**
     * The request target.
     */
    protected string $target;

    /**
     * The HTTP method.
     */
    protected string $method;

    /**
     * @param string $method The request's HTTP method
     * @param UriInterface|string $uri The request's URI 
     * @param string $version The request's HTTP protocol version
     * @param string[] $cookieParams The request's cookie parameters
     * @param StreamInterface $body The request's body
     * 
     * @throws \InvalidArgumentException For any invalid argument
     **/
    public function __construct(
        string $method,
        UriInterface|string $uri,
        string $version = "HTTP/1.1",
        StreamInterface $body = null
    ) {
        $this->method = $this->validateMethod($method);
        $this->uri = $this->validateUri($uri);
        $this->version = $this->validateVersion($version);
        $this->body = $body ?? new Stream('php://input');
        $this->setRequestTarget();
        $this->headers['Host'] = preg_split('/\n/', $this->uri->getHost());
    }

    public function getRequestTarget()
    {
        return $this->target;
    }

    public function withRequestTarget($requestTarget)
    {
        $instance = clone $this;
        $instance->target = $requestTarget;
        return $instance;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        $method = $this->validateMethod($method);
        $instance = clone $this;
        $instance->method = $method;
        return $instance;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $newHeaders = $this->headers;
        if ($preserveHost) {
            $found = $this->findHeader('Host');
            $host = $uri->getHost();
            if (!$found && !empty($host)) {
                $newHeaders['Host'] = $uri->getHost();
            }
        }
        $instance = clone $this;
        $instance->uri = $uri;
        return $instance;
    }

    /**
     * @throws \InvalidArgumentException For invalid HTTP methods
     */
    protected function validateMethod(string $method)
    {
        if (!preg_match(self::VALID_METHODS, $method)) {
            throw new \InvalidArgumentException("Invalid HTTP method");
        }
        return strtoupper($method);
    }

    protected function validateUri($uri)
    {
        if (isset($uri) && is_string($uri)) {
            $uri = new Uri($uri);
        }
        return $uri;
    }

    protected function setRequestTarget()
    {
        if (!isset($this->uri)) {
            $target = '/';
        }
        if ($this->method == 'CONNECT' && !empty($this->uri->getAuthority())) {
            $target = $this->uri->getAuthority();
        }
        $target = (!empty($this->uri->getPath())) ? $this->uri->getPath() : '/';
        if (preg_match('/GET/', $this->method)) {
            $target .= ($this->uri->getQuery()) ? '?' . $this->uri->getQuery() : '';
        }
        $this->target = $target;
    }
}
