<?php
declare(strict_types=1);

namespace App\Http;

use App\Context;
use RuntimeException;

final class Router
{
  /** @var array<int, array{method:string,pattern:string,regex:string,paramNames:array<int,string>,handler:mixed,middleware:array<int,mixed>}> */
  private array $routes = [];

  /** @param array<int, mixed> $middleware */
  public function get(string $pattern, $handler, array $middleware = []): void
  {
    $this->add('GET', $pattern, $handler, $middleware);
  }

  /** @param array<int, mixed> $middleware */
  public function post(string $pattern, $handler, array $middleware = []): void
  {
    $this->add('POST', $pattern, $handler, $middleware);
  }

  /** @param array<int, mixed> $middleware */
  public function add(string $method, string $pattern, $handler, array $middleware = []): void
  {
    $pattern = $pattern === '' ? '/' : $pattern;
    if ($pattern !== '/' && str_ends_with($pattern, '/')) {
      $pattern = rtrim($pattern, '/');
    }

    $paramNames = [];
    $regex = preg_replace_callback('#\\{([a-zA-Z_][a-zA-Z0-9_]*)\\}#', static function (array $m) use (&$paramNames): string {
      $paramNames[] = $m[1];
      return '([^/]+)';
    }, $pattern);
    if (!is_string($regex)) {
      throw new RuntimeException('Failed to compile route pattern');
    }
    $regex = '#^' . $regex . '$#';

    $this->routes[] = [
      'method' => strtoupper($method),
      'pattern' => $pattern,
      'regex' => $regex,
      'paramNames' => $paramNames,
      'handler' => $handler,
      'middleware' => $middleware,
    ];
  }

  public function dispatch(Request $req, Context $ctx): Response
  {
    $method = $req->method();
    $path = $req->path();

    foreach ($this->routes as $route) {
      if ($route['method'] !== $method) {
        continue;
      }

      $matches = [];
      if (preg_match($route['regex'], $path, $matches) !== 1) {
        continue;
      }

      $params = [];
      foreach ($route['paramNames'] as $i => $name) {
        $params[$name] = $matches[$i + 1] ?? '';
      }

      $handler = $route['handler'];
      $middleware = $route['middleware'];

      $core = function (Request $req, array $params) use ($handler, $ctx): Response {
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
          $class = $handler[0];
          $method = $handler[1];
          $controller = new $class($ctx);
          /** @var callable $callable */
          $callable = [$controller, $method];
          return $callable($req, $params);
        }

        if (is_callable($handler)) {
          return $handler($req, $params, $ctx);
        }

        throw new RuntimeException('Invalid route handler');
      };

      $next = $core;
      for ($i = count($middleware) - 1; $i >= 0; $i--) {
        $mw = $middleware[$i];
        $next = function (Request $req, array $params) use ($mw, $next, $ctx): Response {
          if (is_string($mw)) {
            $obj = new $mw();
            if (!$obj instanceof Middleware) {
              throw new RuntimeException('Middleware must implement ' . Middleware::class);
            }
            return $obj->handle($req, $params, $ctx, static fn(Request $r, array $p): Response => $next($r, $p));
          }
          if ($mw instanceof Middleware) {
            return $mw->handle($req, $params, $ctx, static fn(Request $r, array $p): Response => $next($r, $p));
          }
          if (is_callable($mw)) {
            return $mw($req, $params, $ctx, static fn(Request $r, array $p): Response => $next($r, $p));
          }
          throw new RuntimeException('Invalid middleware');
        };
      }

      return $next($req, $params);
    }

    if ($req->isApi()) {
      return Response::json(['error' => 'not_found'], 404);
    }
    return Response::html('<h1>404</h1><p>Not found.</p>', 404);
  }
}

