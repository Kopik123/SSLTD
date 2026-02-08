<?php
declare(strict_types=1);

namespace App;

use App\Database\Db;
use App\Http\Auth;
use App\Http\Csrf;
use App\Http\Session;
use App\Support\Config;

final class Context
{
  private Config $config;
  private Db $db;
  private Session $session;
  private string $basePath;
  private Auth $auth;
  private Csrf $csrf;

  public function __construct(Config $config, Db $db, Session $session, string $basePath = '')
  {
    $this->config = $config;
    $this->db = $db;
    $this->session = $session;
    $this->basePath = $basePath;
    $this->auth = new Auth($db, $session, $config);
    $this->csrf = new Csrf($session);
  }

  public function config(): Config
  {
    return $this->config;
  }

  public function db(): Db
  {
    return $this->db;
  }

  public function session(): Session
  {
    return $this->session;
  }

  public function basePath(): string
  {
    return $this->basePath;
  }

  public function auth(): Auth
  {
    return $this->auth;
  }

  public function csrf(): Csrf
  {
    return $this->csrf;
  }
}
