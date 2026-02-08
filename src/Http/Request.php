<?php
declare(strict_types=1);

namespace App\Http;

final class Request
{
  /** @var array<string, string> */
  private array $server;
  /** @var array<string, string> */
  private array $query;
  /** @var array<string, mixed> */
  private array $post;
  /** @var array<string, mixed> */
  private array $files;
  private ?string $basePath = null;
  private bool $bodyRead = false;
  private ?string $body = null;
  private bool $jsonParsed = false;
  /** @var array<string, mixed>|null */
  private ?array $json = null;

  /** @param array<string, string> $server
   *  @param array<string, string> $query
   *  @param array<string, mixed> $post
   *  @param array<string, mixed> $files
   */
  private function __construct(array $server, array $query, array $post, array $files)
  {
    $this->server = $server;
    $this->query = $query;
    $this->post = $post;
    $this->files = $files;
  }

  public static function fromGlobals(): self
  {
    /** @var array<string, string> $server */
    $server = [];
    foreach ($_SERVER as $k => $v) {
      if (is_string($v)) {
        $server[$k] = $v;
      }
    }

    /** @var array<string, string> $query */
    $query = [];
    foreach ($_GET as $k => $v) {
      if (is_string($v)) {
        $query[$k] = $v;
      }
    }

    /** @var array<string, mixed> $post */
    $post = $_POST;

    /** @var array<string, mixed> $files */
    $files = $_FILES;

    return new self($server, $query, $post, $files);
  }

  public function method(): string
  {
    $m = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    if ($m === 'POST') {
      $override = $this->post['_method'] ?? null;
      if (is_string($override) && $override !== '') {
        return strtoupper($override);
      }
    }
    return $m;
  }

  public function path(): string
  {
    $uri = $this->server['REQUEST_URI'] ?? '/';
    $path = (string)parse_url($uri, PHP_URL_PATH);
    if ($path === '') {
      $path = '/';
    }

    // If served from a subdirectory (e.g. http://localhost/ss_ltd/),
    // strip the script base path so routes stay stable.
    $base = $this->basePath();
    if ($base !== '') {
      if ($path === $base) {
        $path = '/';
      } elseif (str_starts_with($path, $base . '/')) {
        $path = substr($path, strlen($base));
        if ($path === '') {
          $path = '/';
        }
      }
    }

    if ($path !== '/' && str_ends_with($path, '/')) {
      $path = rtrim($path, '/');
    }
    return $path;
  }

  public function basePath(): string
  {
    if ($this->basePath !== null) {
      return $this->basePath;
    }

    $scriptName = $this->server['SCRIPT_NAME'] ?? '';
    if (!is_string($scriptName) || $scriptName === '') {
      $this->basePath = '';
      return $this->basePath;
    }

    $dir = str_replace('\\', '/', dirname($scriptName));
    if ($dir === '/' || $dir === '.' || $dir === '\\') {
      $this->basePath = '';
      return $this->basePath;
    }

    $dir = rtrim($dir, '/');
    $this->basePath = $dir;
    return $this->basePath;
  }

  public function ip(): string
  {
    return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
  }

  public function isApi(): bool
  {
    return str_starts_with($this->path(), '/api/');
  }

  public function header(string $name): ?string
  {
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    $v = $this->server[$key] ?? null;
    return is_string($v) ? $v : null;
  }

  public function bearerToken(): ?string
  {
    $auth = $this->header('Authorization');
    if (!is_string($auth)) {
      return null;
    }

    // Be tolerant of minor formatting differences.
    // Accept:
    // - "Bearer <token>" (standard)
    // - "Bearer<token>"  (no space)
    // - "Bearer:<token>" (no space + colon)
    $auth = trim($auth);
    if (stripos($auth, 'Bearer') !== 0) {
      return null;
    }
    $t = trim(substr($auth, 6));
    if (str_starts_with($t, ':')) {
      $t = trim(substr($t, 1));
    }
    return $t === '' ? null : $t;
  }

  public function query(string $key, ?string $default = null): ?string
  {
    return $this->query[$key] ?? $default;
  }

  /** @return mixed */
  public function input(string $key, $default = null)
  {
    if (array_key_exists($key, $this->post)) {
      return $this->post[$key];
    }
    if (array_key_exists($key, $this->query)) {
      return $this->query[$key];
    }
    return $default;
  }

  /** @return array<string, mixed> */
  public function files(): array
  {
    return $this->files;
  }

  public function rawBody(): string
  {
    if ($this->bodyRead) {
      return $this->body ?? '';
    }
    $this->bodyRead = true;
    $raw = file_get_contents('php://input');
    $this->body = is_string($raw) ? $raw : '';
    return $this->body;
  }

  /** @return array<string, mixed>|null */
  public function json(): ?array
  {
    if ($this->jsonParsed) {
      return $this->json;
    }
    $this->jsonParsed = true;

    $ct = $this->header('Content-Type') ?? '';
    if (stripos($ct, 'application/json') === false) {
      $this->json = null;
      return null;
    }
    $raw = $this->rawBody();
    if ($raw === '') {
      $this->json = null;
      return null;
    }
    $decoded = json_decode($raw, true);
    $this->json = is_array($decoded) ? $decoded : null;
    return $this->json;
  }
}
