<?php

declare(strict_types=1);

namespace Tym\Smart\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Tym\Smart\Http\Message\Request;

final class RequestTest extends TestCase
{
    private RequestInterface $request;

    public function setUp(): void
    {
        $this->request = new Request('GET', 'http://localhost:8000/register');
    }

    public function testGetRequestTarget()
    {
        $this->assertSame('/register', $this->request->getRequestTarget());
    }

    public function testWithRequestTarget()
    {
        $request = $this->request->withRequestTarget('/login');
        $this->assertSame('/login', $request->getRequestTarget());
    }

    public function testGetMethod()
    {
        $this->assertSame('GET', $this->request->getMethod());
    }

    public function testWithMethod()
    {
        $request = $this->request->withMethod('POST');
        $this->assertSame('POST', $request->getMethod());
    }

    public function testGetUri()
    {
        $this->assertSame('http://localhost:8000/register', (string)$this->request->getUri());
    }
    
    public function testWithUri()
    {
        $request=$this->request->withUri($this->request->getUri()->withPath('login'));
        $this->assertSame('http://localhost:8000/login', (string)$request->getUri());
    }
}
