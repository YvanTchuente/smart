<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\MessageInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
abstract class Message implements MessageInterface
{
    /**
     * The message body.
     */
    protected ?StreamInterface $body;

    /**
     * The HTTP protocol version.
     */
    protected string $version;

    /**
     * The message header values.
     * 
     * @var string[][]
     */
    protected array $headers = [];

    public function getProtocolVersion()
    {
        return $this->version;
    }

    public function withProtocolVersion($version)
    {
        $version = $this->validateVersion($version);
        $instance = clone $this;
        $instance->version = $version;
        return $instance;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        return (bool) $this->findHeader($name);
    }

    public function getHeader($name)
    {
        $header_values = [];
        $name = $this->findHeader($name);
        if ($name) {
            $header_values = $this->getHeaders()[$name];
        }
        return $header_values;
    }

    public function getHeaderLine($name)
    {
        $headerLine = '';
        $name = $this->findHeader($name);
        if ($name) {
            $headerLine = implode(",", $this->getHeader($name));
        }
        return $headerLine;
    }

    public function withHeader($name, $value)
    {
        $this->validateHeader($name, $value);
        $new_headers = $this->headers;
        if (is_array($value)) {
            $new_headers[$name] = $value;
        } else if (preg_match('/((.+),|;)+/', $value) && !preg_match('/Date|Expires/i', $name)) {
            $new_headers[$name] = preg_split('/,|;/', $value);
        } else {
            $new_headers[$name] = preg_split('/\n/', $value);
        }
        $instance = clone $this;
        $instance->headers = $new_headers;
        return $instance;
    }

    public function withAddedHeader($name, $value)
    {
        $this->validateHeader($name, $value);
        $new_headers = $this->headers;
        if (is_array($value)) {
            array_push($new_headers[$name], $value);
        } else {
            $new_headers[$name][] = $value;
        }
        $instance = clone $this;
        $instance->headers = $new_headers;
        return $instance;
    }

    public function withoutHeader($name)
    {
        $new_headers = $this->headers;
        if ($this->findHeader($name)) {
            unset($new_headers[$name]);
        }
        $instance = clone $this;
        $instance->headers = $new_headers;
        return $instance;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        if (!$body->isSeekable()) {
            throw new \InvalidArgumentException('The body is invalid');
        }
        $instance = clone $this;
        $instance->body = $body;
        return $instance;
    }

    protected function validateVersion(string $version)
    {
        if (!preg_match('/\d\.\d/', $version)) {
            throw new \InvalidArgumentException("$version is missing the protocol version number");
        }
        $version = preg_replace('/[^0-9\.]/', '', $version);
        return $version;
    }

    /**
     * Determines whether a header exist in the list of headers
     * 
     * Searches a header by its name case-insentively and returns
     * the stored name of the header if it was found or false
     * otherwise
     * 
     * @param string $name The header name.
     * 
     * @return string|false
     **/
    protected function findHeader(string $name)
    {
        foreach (array_keys($this->getHeaders()) as $header) {
            $hasFound = (bool) preg_match("/$name/i", $header);
            if ($hasFound) {
                $headerName = $name;
                break;
            }
        }
        if (isset($headerName)) {
            return $headerName;
        }
        return false;
    }

    /**
     * @throws \InvalidArgumentException For invalid header names or values.
     */
    private function validateHeader($name, $value)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException("Invalid header name");
        }
        if (!is_string($value) && !is_array($value)) {
            throw new \InvalidArgumentException("Invalid header value(s)");
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!is_string($item)) {
                    throw new \InvalidArgumentException("Invalid header values");
                }
            }
        }
    }
}
