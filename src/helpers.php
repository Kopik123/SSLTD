<?php
declare(strict_types=1);

use App\Context;

function e(?string $value): string
{
  return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url_path(string $path): string
{
  if ($path === '') {
    return '/';
  }
  return $path[0] === '/' ? $path : ('/' . $path);
}

function app_url(Context $ctx, string $path): string
{
  $base = $ctx->basePath();
  $p = url_path($path);
  if ($base === '') {
    return $p;
  }
  if ($p === '/') {
    return $base . '/';
  }
  return $base . $p;
}

function csrf_field(Context $ctx): string
{
  $token = $ctx->csrf()->token();
  return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
}

function current_user(Context $ctx): ?array
{
  return $ctx->auth()->user();
}

function flash(Context $ctx, string $key): ?string
{
  return $ctx->session()->consumeFlash($key);
}
