<?php

declare(strict_types=1);

namespace Tym\Smart\Tests;

use PHPUnit\Framework\TestCase;
use Tym\Smart\Http\Message\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

final class ServerRequestTest extends TestCase
{
    private ServerRequestInterface $request;

    public function setUp(): void
    {
        $this->request = new ServerRequest('GET', 'http://localhost:8000/register', $_SERVER, $_COOKIE);
    }

    public function testGetServerParams()
    {
        $this->assertSame($_SERVER, $this->request->getServerParams());
    }

    public function testGetCookieParams()
    {
        $this->assertSame($_COOKIE, $this->request->getCookieParams());
    }

    public function testWithCookieParams()
    {
        $request = $this->request->withCookieParams(['username' => 'Yvan']);
        $this->assertSame(['username' => 'Yvan'], $request->getCookieParams());
    }

    public function testGetQueryParams()
    {
        $this->assertSame([], $this->request->getQueryParams());
    }

    public function testWithQueryParams()
    {
        $request = $this->request->withCookieParams(['username' => 'Yvan']);
        $this->assertSame(['username' => 'Yvan'], $request->getCookieParams());
    }

    public function testGetUploadedFiles()
    {
        $this->assertSame([], $this->request->getUploadedFiles());
    }

    public function testWithUploadedFiles()
    {
        $request = $this->request->withUploadedFiles([]);
        $this->assertSame([], $request->getUploadedFiles());
    }

    public function testGetParsedBody()
    {
        $this->assertNull($this->request->getParsedBody());
    }

    public function testWithParsedBody()
    {
        $request = $this->request->withParsedBody(['username' => 'Yvan']);
        $this->assertSame(['username' => 'Yvan'], $request->getParsedBody());
    }

    public function testGetAttributes()
    {
        $this->assertSame([], $this->request->getAttributes());
    }

    public function testGetAttribute()
    {
        $this->assertNull($this->request->getAttribute('username'));
    }

    public function testWithAttribute()
    {
        $request = $this->request->withAttribute('username', 'Yvan');
        $this->assertSame('Yvan', $request->getAttribute('username'));
    }

    public function testWithoutAttribute()
    {
        $request = $this->request->withAttribute('username', 'Yvan');
        $this->assertNull($request->withoutAttribute('username')->getAttribute('username'));
    }
}
