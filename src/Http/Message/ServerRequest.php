<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Message;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * Server parameters.
     * 
     * @var string[]
     */
    private array $serverParams = [];

    /**
     * Cookie parameters.
     */
    private array $cookieParams = [];

    /**
     * The request's URI query parameters.
     * 
     * @var string[]
     */
    private array $queryParams = [];

    /**
     * The deserialized body parameters.
     */
    private array|object|null $parsedBody = null;

    /**
     * The request attributes.
     */
    private array $attributes = [];

    /**
     * The list of uploaded files.
     * 
     * @var UploadedFileInterface[]
     */
    private array $uploadedFiles = [];

    /**
     * @param string $method The request's HTTP method.
     * @param UriInterface|string $uri The request URI. 
     * @param array $serverParams Server parameters.
     * @param string[][] $headers The request header values.
     * @param string $version The HTTP protocol version.
     * @param StreamInterface $body The request body.
     * @param array $attributes The request attributes.
     * 
     * @throws \InvalidArgumentException For any invalid argument
     **/
    public function __construct(
        string $method,
        UriInterface|string $uri,
        array $serverParams = [],
        array $cookieParams = [],
        string $version = "HTTP/1.1",
        StreamInterface $body = null,
        array $attributes = []
    ) {
        $this->method = $this->validateMethod($method);
        $this->uri = $this->validateUri($uri);
        $this->serverParams = $serverParams;
        $this->cookieParams = $cookieParams;
        $this->body = $body ?? new Stream('php://input');
        $this->version = $this->validateVersion($version);
        $this->attributes = $attributes;
        $this->setRequestTarget();
        $this->setQueryParams();
        $this->headers['Host'] = preg_split('/\n/', $this->uri->getHost());
    }

    public function getServerParams()
    {
        return $this->serverParams;
    }

    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies)
    {
        $instance = clone $this;
        $instance->cookieParams = $cookies;
        return $instance;
    }

    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query)
    {
        $instance = clone $this;
        $instance->queryParams = $query;
        return $instance;
    }

    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        $instance = clone $this;
        foreach (array_values($uploadedFiles) as $uploadedFile) {
            if (!($uploadedFile instanceof UploadedFileInterface)) {
                throw new \InvalidArgumentException('Must contain instances of UploadedFileInterface');
            }
        }
        $instance->uploadedFiles = $uploadedFiles;
        return $instance;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data)
    {
        if (isset($data)) {
            if (!(is_array($data) and !is_object($data)) and !(!is_array($data) and is_object($data))) {
                throw new \InvalidArgumentException("Unsupported argument type");
            }
        }
        $instance = clone $this;
        $instance->parsedBody = $data;
        return $instance;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value)
    {
        $instance = clone $this;
        $instance->attributes[$name] = $value;
        return $instance;
    }

    public function withoutAttribute($name)
    {
        $instance = clone $this;
        if (isset($this->attributes[$name])) {
            unset($instance->attributes[$name]);
        }
        return $instance;
    }

    private function setQueryParams()
    {
        $params = [];
        $query = $this->uri->getQuery();
        if ($query) {
            if ($query[0] == '?') {
                $query = substr($query, 1);
            }
            foreach (explode('&', $query) as $key => $value) {
                $params[$key] = $value;
            }
        }
        $this->queryParams = $params;
    }
}
