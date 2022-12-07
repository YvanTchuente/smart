<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Message;

use Psr\Http\Message\UriInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class Uri implements UriInterface
{
    /**
     * @var string
     */
    private const VALID_PATH = '/\/?(\.+\/?)?/';

    /**
     * @var string
     */
    private const SUPPORTED_SCHEMES = '/https?|ssh|ftp/i';

    /**
     * @var string
     */
    private const VALID_QUERY_STRING = '/(\w+(=\w)?&?)+/';

    /**
     * @var string
     */
    private const VALID_HOSTNAMES = '/\w{5,9}|(\d{1,3}(\b|\.)){4}|\w{3}\.\w+\.\w{2}/';

    /** 
     * The URI components.
     * 
     * @var string[]
     */
    private array $components = [];

    /**
     * The URI query parameters.
     * 
     * @var string[]
     */
    private array $queryParams = [];

    /**
     * @param string $uri The URI to parse.
     * 
     * @throws \InvalidArgumentException If the URI cannot be parsed.
     **/
    public function __construct(string $uri = '')
    {
        $this->components = $this->getComponents($uri);
        $this->queryParams = $this->getQueryParams($this->components['query'] ?? null);
        if (empty($this->components['port']) && !empty($this->components['scheme'])) {
            if ($port = $this->portOf($this->components['scheme'])) {
                $this->components['port'] = $port;
            }
        }
    }

    public function getScheme()
    {
        if (empty($this->components['scheme'])) {
            return '';
        }
        $scheme = strtolower($this->components['scheme']);
        return $scheme;
    }

    public function getAuthority()
    {
        $authority = '';
        if ($this->getUserInfo()) {
            $authority .= $this->getUserInfo() . '@';
        }
        $authority .= $this->components['host'] ?? '';
        if (!empty($this->components['port'])) {
            $authority .= ':' . $this->components['port'];
        }
        return $authority;
    }

    public function getUserInfo()
    {
        if (empty($this->components['user'])) {
            return '';
        }
        $userInfo = $this->components['user'];
        if (isset($this->components['pass'])) {
            $userInfo .= ":" . $this->components['pass'];
        }
        return $userInfo;
    }

    public function getHost()
    {
        if (empty($this->components['host'])) {
            return '';
        }
        $host = strtolower($this->components['host']);
        return $host;
    }

    public function getPort()
    {
        if (empty($this->components['port'])) {
            return null;
        }
        $port = (int) $this->components['port'];
        return $port;
    }

    public function getPath()
    {
        if (empty($this->components['path'])) {
            return '';
        }
        $encodedParts = array_map("rawurlencode", explode('/', $this->components['path']));
        $path = implode("/", $encodedParts);
        return $path;
    }

    public function getQuery()
    {
        if (empty($this->queryParams)) {
            return '';
        }
        $query = '';
        foreach ($this->queryParams as $key => $value) {
            $query .= rawurlencode($key);
            if (!empty($value)) {
                $query .= '=' . rawurlencode($value);
            }
            $query .= '&';
        }
        $query = substr($query, 0, -1);
        return $query;
    }

    public function getFragment()
    {
        if (empty($this->components['fragment'])) {
            return '';
        }
        $fragment = rawurlencode($this->components['fragment']);
        return $fragment;
    }

    public function withScheme($scheme)
    {
        $instance = clone $this;
        if (empty($scheme) and $this->getScheme()) {
            unset($instance->components['scheme']);
        } else {
            if (is_string($scheme) and preg_match(self::SUPPORTED_SCHEMES, $scheme)) {
                $instance->components['scheme'] = $scheme;
                if (empty($instance->components['port']) && !empty($instance->components['scheme'])) {
                    $port = $instance->portOf($instance->components['scheme']);
                    if ($port) {
                        $instance->components['port'] = $port;
                    }
                }
            } else {
                throw new \InvalidArgumentException('Unsupported scheme');
            }
        }
        return $instance;
    }

    public function withUserInfo($user, $password = null)
    {
        $instance = clone $this;
        if (empty($user) and $this->getUserInfo()) {
            unset($instance->components['user']);
        } else {
            if (!is_string($user) || !($password && is_string($password))) {
                throw new \InvalidArgumentException('User name is invalid');
            }
            $instance->components['user'] = $user;
            if ($password) {
                $instance->components['pass'] = $password;
            }
        }
        return $instance;
    }

    public function withHost($host)
    {
        $instance = clone $this;
        if (empty($host) and $this->getHost()) {
            unset($instance->components['host']);
        } else {
            if (is_string($host) and preg_match(self::VALID_HOSTNAMES, strtolower($host))) {
                $instance->components['host'] = $host;
            } else {
                throw new \InvalidArgumentException('Invalid host name');
            }
        }
        return $instance;
    }

    public function withPort($port = null)
    {
        $instance = clone $this;
        if (empty($port)) {
            unset($instance->components['port']);
        } else {
            $valid_ports = range(1, 10000);
            if (is_integer($port) and in_array($port, $valid_ports)) {
                $instance->components['port'] = $port;
            } else {
                throw new \InvalidArgumentException('Invalid port');
            }
        }
        return $instance;
    }

    public function withPath($path)
    {
        $instance = clone $this;
        if (!is_string($path) or !preg_match(self::VALID_PATH, $path)) {
            throw new \InvalidArgumentException('Invalid path');
        }
        $instance->components['path'] = $path;
        return $instance;
    }

    public function withQuery($query)
    {
        $instance = clone $this;
        if (empty($query) and $this->getQuery()) {
            $instance->components['query'] = '';
            $instance->queryParams = [];
        } else {
            if (is_string($query) and preg_match(self::VALID_QUERY_STRING, $query)) {
                $instance->components['query'] = $query;
                $instance->queryParams = $this->getQueryParams($query);
            } else {
                throw new \InvalidArgumentException('Invalid query string');
            }
        }
        return $instance;
    }

    public function withFragment($fragment)
    {
        $instance = clone $this;
        if (empty($fragment) and $this->getFragment()) {
            unset($instance->components['fragment']);
        } else {
            if (!is_string($fragment)) {
                throw new \InvalidArgumentException('Invalid argument');
            }
            $instance->components['fragment'] = $fragment;
        }
        return $instance;
    }

    public function __toString()
    {
        $uri = ($this->getScheme()) ? $this->getScheme() . '://' : '';
        if ($this->getAuthority()) {
            $uri .= $this->getAuthority();
        } else {
            $uri .= ($this->getHost()) ? $this->getHost() : '';
            $uri .= ($this->getPort()) ? ':' . $this->getPort() : '';
        }
        $path = $this->getPath();
        if ($path) {
            if ($path[0] != '/') {
                $uri .= '/' . $path;
            } else {
                $uri .= $path;
            }
        }
        $uri .= ($this->getQuery()) ? '?' . $this->getQuery() : '';
        $uri .= ($this->getFragment()) ? '#' . $this->getFragment() : '';
        return $uri;
    }

    /**
     * Retrieves the components of a given URI.
     */
    private function getComponents(string $uri)
    {
        $components = parse_url($uri);
        if (is_null($components) or $components === false) {
            throw new \InvalidArgumentException('Invalid URI');
        }
        return $components;
    }

    /**
     * Retrieves the query parameters from a given query.
     * 
     * @return array
     */
    private function getQueryParams(?string $query)
    {
        $params = [];
        if ($query) {
            foreach (explode('&', $query) as $pair) {
                $pair_elements = explode('=', $pair);
                if (count($pair_elements) == 2) {
                    $params[$pair_elements[0]] = $pair_elements[1];
                } else {
                    $params[$pair_elements[0]] = null;
                }
            }
        }
        return $params;
    }

    /**
     * Get the port number equivalent to a given scheme.
     * 
     * @return int|null
     */
    private function portOf(string $scheme)
    {
        $port = null;
        switch (true) {
            case (preg_match('/http/i', $scheme)):
                $port = 80;
                break;
            case (preg_match('/https/i', $scheme)):
                $port = 443;
                break;
            case (preg_match('/ftp/i', $scheme)):
                $port = 21;
                break;
            case (preg_match('/ssh/i', $scheme)):
                $port = 22;
                break;
        }
        return $port;
    }
}
