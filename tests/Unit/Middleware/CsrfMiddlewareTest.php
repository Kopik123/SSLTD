<?php
declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Middleware\CsrfMiddleware;
use App\Context;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

final class CsrfMiddlewareTest extends TestCase
{
    private CsrfMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CsrfMiddleware();
    }

    public function testAllowsGetRequests(): void
    {
        $request = $this->createMockRequest('GET', false);
        $context = $this->createMockContext();
        $nextCalled = false;
        
        $next = function ($req, $params) use (&$nextCalled) {
            $nextCalled = true;
            return Response::html('OK', 200);
        };
        
        $response = $this->middleware->handle($request, [], $context, $next);
        
        $this->assertTrue($nextCalled, 'Next middleware should be called for GET requests');
        $this->assertEquals(200, $response->status());
    }

    public function testAllowsHeadRequests(): void
    {
        $request = $this->createMockRequest('HEAD', false);
        $context = $this->createMockContext();
        $nextCalled = false;
        
        $next = function ($req, $params) use (&$nextCalled) {
            $nextCalled = true;
            return Response::html('OK', 200);
        };
        
        $response = $this->middleware->handle($request, [], $context, $next);
        
        $this->assertTrue($nextCalled, 'Next middleware should be called for HEAD requests');
    }

    public function testAllowsOptionsRequests(): void
    {
        $request = $this->createMockRequest('OPTIONS', false);
        $context = $this->createMockContext();
        $nextCalled = false;
        
        $next = function ($req, $params) use (&$nextCalled) {
            $nextCalled = true;
            return Response::html('OK', 200);
        };
        
        $response = $this->middleware->handle($request, [], $context, $next);
        
        $this->assertTrue($nextCalled, 'Next middleware should be called for OPTIONS requests');
    }

    public function testAllowsApiRequests(): void
    {
        $request = $this->createMockRequest('POST', true); // API request
        $context = $this->createMockContext();
        $nextCalled = false;
        
        $next = function ($req, $params) use (&$nextCalled) {
            $nextCalled = true;
            return Response::html('OK', 200);
        };
        
        $response = $this->middleware->handle($request, [], $context, $next);
        
        $this->assertTrue($nextCalled, 'Next middleware should be called for API requests');
    }

    public function testRejectsPostWithoutCsrfToken(): void
    {
        $request = $this->createMockRequest('POST', false, null);
        $context = $this->createMockContext(false); // Invalid token
        $nextCalled = false;
        
        $next = function ($req, $params) use (&$nextCalled) {
            $nextCalled = true;
            return Response::html('OK', 200);
        };
        
        $response = $this->middleware->handle($request, [], $context, $next);
        
        $this->assertFalse($nextCalled, 'Next middleware should NOT be called without valid CSRF token');
        $this->assertEquals(419, $response->status());
    }

    public function testRejectsPostWithInvalidCsrfToken(): void
    {
        $request = $this->createMockRequest('POST', false, 'invalid_token');
        $context = $this->createMockContext(false); // Invalid token
        $nextCalled = false;
        
        $next = function ($req, $params) use (&$nextCalled) {
            $nextCalled = true;
            return Response::html('OK', 200);
        };
        
        $response = $this->middleware->handle($request, [], $context, $next);
        
        $this->assertFalse($nextCalled, 'Next middleware should NOT be called with invalid CSRF token');
        $this->assertEquals(419, $response->status());
    }

    public function testAllowsPostWithValidCsrfToken(): void
    {
        $request = $this->createMockRequest('POST', false, 'valid_token');
        $context = $this->createMockContext(true); // Valid token
        $nextCalled = false;
        
        $next = function ($req, $params) use (&$nextCalled) {
            $nextCalled = true;
            return Response::html('OK', 200);
        };
        
        $response = $this->middleware->handle($request, [], $context, $next);
        
        $this->assertTrue($nextCalled, 'Next middleware should be called with valid CSRF token');
        $this->assertEquals(200, $response->status());
    }

    public function testAllowsPutWithValidCsrfToken(): void
    {
        $request = $this->createMockRequest('PUT', false, 'valid_token');
        $context = $this->createMockContext(true);
        $nextCalled = false;
        
        $next = function ($req, $params) use (&$nextCalled) {
            $nextCalled = true;
            return Response::html('OK', 200);
        };
        
        $response = $this->middleware->handle($request, [], $context, $next);
        
        $this->assertTrue($nextCalled);
    }

    public function testAllowsDeleteWithValidCsrfToken(): void
    {
        $request = $this->createMockRequest('DELETE', false, 'valid_token');
        $context = $this->createMockContext(true);
        $nextCalled = false;
        
        $next = function ($req, $params) use (&$nextCalled) {
            $nextCalled = true;
            return Response::html('OK', 200);
        };
        
        $response = $this->middleware->handle($request, [], $context, $next);
        
        $this->assertTrue($nextCalled);
    }

    private function createMockRequest(string $method, bool $isApi, ?string $csrfToken = null): Request
    {
        $mock = $this->createMock(Request::class);
        $mock->method('method')->willReturn($method);
        $mock->method('isApi')->willReturn($isApi);
        $mock->method('input')->willReturnCallback(function ($key) use ($csrfToken) {
            return $key === '_csrf' ? $csrfToken : null;
        });
        return $mock;
    }

    private function createMockContext(bool $csrfValid = true): Context
    {
        $csrf = $this->createMock(\App\Support\Csrf::class);
        $csrf->method('validate')->willReturn($csrfValid);
        
        $context = $this->createMock(Context::class);
        $context->method('csrf')->willReturn($csrf);
        
        return $context;
    }
}
