<?php
declare(strict_types=1);

namespace App\Http;

use App\Support\Crypto;

final class Csrf
{
  private Session $session;

  public function __construct(Session $session)
  {
    $this->session = $session;
  }

  public function token(): string
  {
    $t = $this->session->get('__csrf');
    if (is_string($t) && $t !== '') {
      return $t;
    }
    $t = Crypto::randomToken(32);
    $this->session->set('__csrf', $t);
    return $t;
  }

  public function validate(?string $token): bool
  {
    $t = $this->session->get('__csrf');
    if (!is_string($t) || $t === '' || !is_string($token) || $token === '') {
      return false;
    }
    return Crypto::safeEquals($t, $token);
  }
}

