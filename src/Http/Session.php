<?php
declare(strict_types=1);

namespace App\Http;

use App\Support\Config;

final class Session
{
  private bool $started = false;

  private function __construct()
  {
  }

  public static function start(Config $config): self
  {
    $self = new self();

    if (session_status() === PHP_SESSION_ACTIVE) {
      $self->started = true;
      return $self;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    $isHttps = str_starts_with($config->getString('APP_URL'), 'https://');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');

    session_name('ss_ltd');
    session_start();
    $self->started = true;

    if (!isset($_SESSION['__inited'])) {
      $_SESSION['__inited'] = true;
      session_regenerate_id(true);
    }

    return $self;
  }

  /** @return mixed */
  public function get(string $key, $default = null)
  {
    return $_SESSION[$key] ?? $default;
  }

  /** @param mixed $value */
  public function set(string $key, $value): void
  {
    $_SESSION[$key] = $value;
  }

  public function delete(string $key): void
  {
    unset($_SESSION[$key]);
  }

  public function regenerate(): void
  {
    if ($this->started) {
      session_regenerate_id(true);
    }
  }

  public function flash(string $key, string $value): void
  {
    $_SESSION['__flash'][$key] = $value;
  }

  public function consumeFlash(string $key): ?string
  {
    $v = $_SESSION['__flash'][$key] ?? null;
    unset($_SESSION['__flash'][$key]);
    return is_string($v) ? $v : null;
  }

  public function destroy(): void
  {
    if (!$this->started) {
      return;
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $params['path'] ?: '/', $params['domain'] ?: '', (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
  }
}

