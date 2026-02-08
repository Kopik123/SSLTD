<?php
declare(strict_types=1);

namespace App\Http;

use App\Database\Db;
use App\Support\Config;
use App\Support\Crypto;

final class Auth
{
  private Db $db;
  private Session $session;
  private Config $config;
  private ?array $cachedUser = null;

  public function __construct(Db $db, Session $session, Config $config)
  {
    $this->db = $db;
    $this->session = $session;
    $this->config = $config;
  }

  public function user(): ?array
  {
    if ($this->cachedUser !== null) {
      return $this->cachedUser;
    }

    $userId = $this->session->get('user_id');
    if (!is_int($userId) && !is_string($userId)) {
      return null;
    }

    $u = $this->db->fetchOne('SELECT id, role, name, email, phone, status, created_at, updated_at FROM users WHERE id = :id', [
      'id' => $userId,
    ]);
    $this->cachedUser = $u;
    return $u;
  }

  public function check(): bool
  {
    return $this->user() !== null;
  }

  public function attempt(string $email, string $password): bool
  {
    $u = $this->db->fetchOne('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    if ($u === null) {
      return false;
    }
    if (($u['status'] ?? '') !== 'active') {
      return false;
    }
    $hash = (string)($u['password_hash'] ?? '');
    if (!password_verify($password, $hash)) {
      return false;
    }

    $this->login((int)$u['id']);
    $this->db->execute('UPDATE users SET last_login_at = :ts WHERE id = :id', ['ts' => gmdate('c'), 'id' => (int)$u['id']]);
    return true;
  }

  public function login(int $userId): void
  {
    $this->session->regenerate();
    $this->session->set('user_id', $userId);
    $this->cachedUser = null;
  }

  public function logout(): void
  {
    $this->session->delete('user_id');
    $this->cachedUser = null;
    $this->session->regenerate();
  }

  public function issueApiToken(int $userId, int $ttlDays = 30): string
  {
    $token = Crypto::randomToken(32);
    $hash = Crypto::hashToken($token);
    $now = gmdate('c');
    $exp = gmdate('c', time() + ($ttlDays * 86400));
    $this->db->insert(
      'INSERT INTO api_tokens (user_id, token_hash, created_at, expires_at, last_used_at) VALUES (:uid, :hash, :c, :e, :u)',
      ['uid' => $userId, 'hash' => $hash, 'c' => $now, 'e' => $exp, 'u' => $now]
    );
    return $token;
  }

  /**
   * Refreshes an existing bearer token by issuing a new one and revoking the old.
   * Returns null if the provided token is invalid/expired.
   *
   * @return array{token: string, user: array<string, mixed>}|null
   */
  public function refreshApiToken(string $token, int $ttlDays = 30): ?array
  {
    $token = trim($token);
    if ($token === '') {
      return null;
    }

    $hash = Crypto::hashToken($token);
    $now = gmdate('c');

    $row = $this->db->fetchOne(
      'SELECT t.id AS token_id, t.user_id, u.id, u.role, u.name, u.email, u.phone, u.status, u.created_at, u.updated_at
       FROM api_tokens t
       JOIN users u ON u.id = t.user_id
       WHERE t.token_hash = :h AND t.expires_at > :now AND u.status = :active
       LIMIT 1',
      ['h' => $hash, 'now' => $now, 'active' => 'active']
    );
    if ($row === null) {
      return null;
    }

    // Revoke old token and issue a new one.
    $this->db->execute('UPDATE api_tokens SET expires_at = :now WHERE token_hash = :h', ['now' => $now, 'h' => $hash]);
    $new = $this->issueApiToken((int)$row['user_id'], $ttlDays);

    unset($row['token_id'], $row['user_id']);
    return ['token' => $new, 'user' => $row];
  }

  public function apiUserFromRequest(Request $req): ?array
  {
    $token = $req->bearerToken();
    if ($token === null) {
      return null;
    }
    $hash = Crypto::hashToken($token);

    $row = $this->db->fetchOne(
      'SELECT u.id, u.role, u.name, u.email, u.phone, u.status, u.created_at, u.updated_at
       FROM api_tokens t
       JOIN users u ON u.id = t.user_id
       WHERE t.token_hash = :h AND t.expires_at > :now AND u.status = :active
       LIMIT 1',
      ['h' => $hash, 'now' => gmdate('c'), 'active' => 'active']
    );

    if ($row !== null) {
      $this->db->execute('UPDATE api_tokens SET last_used_at = :ts WHERE token_hash = :h', ['ts' => gmdate('c'), 'h' => $hash]);
    }

    return $row;
  }
}
