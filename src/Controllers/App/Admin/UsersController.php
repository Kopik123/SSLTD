<?php
declare(strict_types=1);

namespace App\Controllers\App\Admin;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Support\Validate;

final class UsersController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    $users = $this->ctx->db()->fetchAll(
      'SELECT id, role, name, email, phone, status, last_login_at, created_at, updated_at
       FROM users
       ORDER BY created_at DESC
       LIMIT 500'
    );

    return $this->page('app/admin_users', [
      'title' => 'Admin: Users',
      'users' => $users,
    ], 'layouts/app');
  }

  public function create(Request $req, array $params): Response
  {
    $role = strtolower(trim((string)$req->input('role', 'client')));
    $name = Validate::str($req->input('name', ''), 255, true);
    $email = Validate::email($req->input('email', ''), 255);
    $phone = Validate::str($req->input('phone', ''), 64, false);
    $password = (string)$req->input('password', '');

    $allowedRoles = ['admin', 'pm', 'client', 'employee', 'subcontractor', 'subcontractor_worker'];
    $errors = [];
    if (!in_array($role, $allowedRoles, true)) $errors[] = 'Invalid role.';
    if ($name === null) $errors[] = 'Name is required.';
    if ($email === null) $errors[] = 'Valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    if ($errors !== []) {
      $this->ctx->session()->flash('error', implode(' ', $errors));
      return $this->redirect('/app/admin/users');
    }

    $existing = $this->ctx->db()->fetchOne('SELECT id FROM users WHERE email = :e LIMIT 1', ['e' => $email]);
    if ($existing !== null) {
      $this->ctx->session()->flash('error', 'Email is already taken.');
      return $this->redirect('/app/admin/users');
    }

    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO users (role, name, email, phone, password_hash, status, created_at, updated_at)
       VALUES (:r, :n, :e, :p, :h, :s, :c, :u)',
      [
        'r' => $role,
        'n' => $name,
        'e' => $email,
        'p' => ($phone === null || $phone === '') ? null : $phone,
        'h' => password_hash($password, PASSWORD_DEFAULT),
        's' => 'active',
        'c' => $now,
        'u' => $now,
      ]
    );

    $actor = $this->ctx->auth()->user();
    if ($actor !== null) {
      $this->audit((int)$actor['id'], 'user_created', 'user', $id, ['role' => $role, 'email' => $email]);
    }

    $this->ctx->session()->flash('notice', 'User created (ID ' . $id . ').');
    return $this->redirect('/app/admin/users');
  }

  public function setPassword(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $password = (string)$req->input('password', '');
    if ($id <= 0) {
      return Response::html('<h1>404</h1><p>User not found.</p>', 404);
    }
    if (strlen($password) < 8) {
      $this->ctx->session()->flash('error', 'Password must be at least 8 characters.');
      return $this->redirect('/app/admin/users');
    }

    $u = $this->ctx->db()->fetchOne('SELECT id, email FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($u === null) {
      return Response::html('<h1>404</h1><p>User not found.</p>', 404);
    }

    $this->ctx->db()->execute('UPDATE users SET password_hash = :h, updated_at = :u WHERE id = :id', [
      'h' => password_hash($password, PASSWORD_DEFAULT),
      'u' => gmdate('c'),
      'id' => $id,
    ]);

    $actor = $this->ctx->auth()->user();
    if ($actor !== null) {
      $this->audit((int)$actor['id'], 'user_password_set', 'user', $id, ['email' => (string)($u['email'] ?? '')]);
    }

    $this->ctx->session()->flash('notice', 'Password updated.');
    return $this->redirect('/app/admin/users');
  }

  public function update(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) {
      return Response::html('<h1>404</h1><p>User not found.</p>', 404);
    }

    $role = strtolower(trim((string)$req->input('role', '')));
    $status = strtolower(trim((string)$req->input('status', 'active')));

    $allowedRoles = ['admin', 'pm', 'client', 'employee', 'subcontractor', 'subcontractor_worker'];
    $allowedStatuses = ['active', 'inactive'];

    $errors = [];
    if (!in_array($role, $allowedRoles, true)) $errors[] = 'Invalid role.';
    if (!in_array($status, $allowedStatuses, true)) $errors[] = 'Invalid status.';

    if ($errors !== []) {
      $this->ctx->session()->flash('error', implode(' ', $errors));
      return $this->redirect('/app/admin/users');
    }

    $target = $this->ctx->db()->fetchOne('SELECT id, role, status, email FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($target === null) {
      return Response::html('<h1>404</h1><p>User not found.</p>', 404);
    }

    $actor = $this->ctx->auth()->user();
    if ($actor !== null && (int)$actor['id'] === $id) {
      // Prevent locking yourself out.
      if ($role !== 'admin') $errors[] = 'You cannot change your own role.';
      if ($status !== 'active') $errors[] = 'You cannot deactivate your own account.';
    }

    if ($errors !== []) {
      $this->ctx->session()->flash('error', implode(' ', $errors));
      return $this->redirect('/app/admin/users');
    }

    $this->ctx->db()->execute('UPDATE users SET role = :r, status = :s, updated_at = :u WHERE id = :id', [
      'r' => $role,
      's' => $status,
      'u' => gmdate('c'),
      'id' => $id,
    ]);

    if ($actor !== null) {
      $this->audit((int)$actor['id'], 'user_updated', 'user', $id, [
        'email' => (string)($target['email'] ?? ''),
        'role' => $role,
        'status' => $status,
      ]);
    }

    $this->ctx->session()->flash('notice', 'User updated.');
    return $this->redirect('/app/admin/users');
  }

  /** @param array<string, mixed> $meta */
  protected function audit(int $actorId, string $action, string $entityType, ?int $entityId, array $meta): void
  {
    $this->ctx->db()->insert(
      'INSERT INTO audit_log (actor_user_id, action, entity_type, entity_id, meta_json, created_at)
       VALUES (:a, :ac, :et, :eid, :m, :c)',
      [
        'a' => $actorId,
        'ac' => $action,
        'et' => $entityType,
        'eid' => $entityId,
        'm' => $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_SLASHES),
        'c' => gmdate('c'),
      ]
    );
  }
}
