<?php
declare(strict_types=1);

namespace App\Http;

use App\Support\RequestId;

final class Response
{
  private int $status;
  /** @var array<string, string> */
  private array $headers;
  private string $body;
  /** @var null|callable */
  private $stream;

  /** @param array<string, string> $headers */
  private function __construct(int $status, array $headers, string $body, ?callable $stream = null)
  {
    $this->status = $status;
    $this->headers = $headers;
    $this->body = $body;
    $this->stream = $stream;
  }

  public static function html(string $html, int $status = 200): self
  {
    return new self($status, ['Content-Type' => 'text/html; charset=utf-8'], $html);
  }

  public static function text(string $text, int $status = 200): self
  {
    return new self($status, ['Content-Type' => 'text/plain; charset=utf-8'], $text);
  }

  /** @param array<string, mixed> $data */
  public static function json(array $data, int $status = 200): self
  {
    $rid = RequestId::get();
    if ($rid !== null && !array_key_exists('request_id', $data)) {
      $data['request_id'] = $rid;
    }
    return new self($status, ['Content-Type' => 'application/json; charset=utf-8'], json_encode($data, JSON_UNESCAPED_SLASHES) ?: '{}');
  }

  public static function redirect(string $to, int $status = 302): self
  {
    return new self($status, ['Location' => $to], '');
  }

  /**
   * Streaming response (used for downloads). The callback is executed after
   * headers are sent.
   *
   * @param array<string, string> $headers
   */
  public static function stream(callable $stream, array $headers = [], int $status = 200): self
  {
    return new self($status, $headers, '', $stream);
  }

  public function withHeader(string $name, string $value): self
  {
    $clone = clone $this;
    $clone->headers[$name] = $value;
    return $clone;
  }

  public function send(): void
  {
    http_response_code($this->status);
    foreach ($this->headers as $k => $v) {
      header($k . ': ' . $v);
    }
    if ($this->stream !== null) {
      ($this->stream)();
      return;
    }
    echo $this->body;
  }
}
