<?php

declare(strict_types=1);

namespace Tym\Smart\Tests;

use PHPUnit\Framework\TestCase;
use Tym\Smart\Http\Message\Uri;
use Psr\Http\Message\UriInterface;

final class UriTest extends TestCase
{
    private UriInterface $uri;

    public function setUp(): void
    {
        $this->uri = new Uri('http://admin:mael@localhost:8080/books/15?author=yvan#name');
    }

    public function testGetScheme()
    {
        $this->assertSame('http', $this->uri->getScheme());
    }

    public function testGetAuthority()
    {
        $this->assertSame('admin:mael@localhost:8080', $this->uri->getAuthority());
    }

    public function testGetUserInfo()
    {
        $this->assertSame('admin:mael', $this->uri->getUserInfo());
    }

    public function testGetHost()
    {
        $this->assertSame('localhost', $this->uri->getHost());
    }

    public function testGetPort()
    {
        $this->assertSame(8080, $this->uri->getPort());
    }

    public function testGetPath()
    {
        $this->assertSame('/books/15', $this->uri->getPath());
    }

    public function testGetQuery()
    {
        $this->assertSame('author=yvan', $this->uri->getQuery());
    }

    public function testGetFragment()
    {
        $this->assertSame('name', $this->uri->getFragment());
    }

    public function testWithScheme()
    {
        $uri = $this->uri->withScheme('https');
        $this->assertSame('https', $uri->getScheme());
    }

    public function testWithUserInfo()
    {
        $uri = $this->uri->withUserInfo('root', 'wxyz');
        $this->assertSame('root:wxyz', $uri->getUserInfo());
    }

    public function testWithHost()
    {
        $uri = $this->uri->withHost('cadexsa.com');
        $this->assertSame('cadexsa.com', $uri->getHost());
    }

    public function testWithPort()
    {
        $uri = $this->uri->withPort(8000);
        $this->assertSame(8000, $uri->getPort());
    }

    public function testWithPath()
    {
        $uri = $this->uri->withPath('/users/20');
        $this->assertSame('/users/20', $uri->getPath());
    }

    public function testWithQuery()
    {
        $uri = $this->uri->withQuery('');
        $this->assertSame('', $uri->getQuery());
    }

    public function testWithFragment()
    {
        $uri = $this->uri->withFragment('administration');
        $this->assertSame('administration', $uri->getFragment());
    }
}
