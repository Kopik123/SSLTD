<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Support\Crypto;

final class AuthController extends Controller
{
  public function loginForm(Request $req, array $params): Response
  {
    if ($this->ctx->auth()->check()) {
      return $this->redirect('/app');
    }
    return $this->page('auth/login', ['title' => 'Login', 'next' => (string)$req->query('next', '/app')], 'layouts/auth');
  }

  public function loginSubmit(Request $req, array $params): Response
  {
    $email = trim((string)$req->input('email', ''));
    $password = (string)$req->input('password', '');

    if ($email === '' || $password === '') {
      $this->ctx->session()->flash('error', 'Email and password are required.');
      return $this->redirect('/login');
    }

    if (!$this->ctx->auth()->attempt($email, $password)) {
      $this->ctx->session()->flash('error', 'Invalid credentials.');
      return $this->redirect('/login');
    }

    $next = (string)$req->input('next', '/app');
    if ($next === '' || $next[0] !== '/') {
      $next = '/app';
    }
    return $this->redirect($next);
  }

  public function registerForm(Request $req, array $params): Response
  {
    if ($this->ctx->auth()->check()) {
      return $this->redirect('/app');
    }
    return $this->page('auth/register', ['title' => 'Register'], 'layouts/auth');
  }

  public function registerSubmit(Request $req, array $params): Response
  {
    $name = trim((string)$req->input('name', ''));
    $email = trim((string)$req->input('email', ''));
    $phone = trim((string)$req->input('phone', ''));
    $password = (string)$req->input('password', '');
    $password2 = (string)$req->input('password_confirm', '');

    $errors = [];
    if ($name === '') $errors[] = 'Name is required.';
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) $errors[] = 'Valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $password2) $errors[] = 'Passwords do not match.';

    $existing = $this->ctx->db()->fetchOne('SELECT id FROM users WHERE email = :e LIMIT 1', ['e' => $email]);
    if ($existing !== null) $errors[] = 'Email already registered.';

    if ($errors !== []) {
      $this->ctx->session()->flash('error', implode(' ', $errors));
      return $this->redirect('/register');
    }

    $now = gmdate('c');
    $userId = (int)$this->ctx->db()->insert(
      'INSERT INTO users (role, name, email, phone, password_hash, status, created_at, updated_at) VALUES (:r, :n, :e, :p, :h, :s, :c, :u)',
      [
        'r' => 'client',
        'n' => $name,
        'e' => $email,
        'p' => $phone === '' ? null : $phone,
        'h' => password_hash($password, PASSWORD_DEFAULT),
        's' => 'active',
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->ctx->auth()->login($userId);
    return $this->redirect('/app');
  }

  public function logout(Request $req, array $params): Response
  {
    $this->ctx->auth()->logout();
    return $this->redirect('/');
  }

  public function forgotPasswordForm(Request $req, array $params): Response
  {
    return $this->page('auth/forgot_password', ['title' => 'Reset password'], 'layouts/auth');
  }

  public function forgotPasswordSubmit(Request $req, array $params): Response
  {
    $email = trim((string)$req->input('email', ''));
    $token = null;

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
      $user = $this->ctx->db()->fetchOne('SELECT id FROM users WHERE email = :e AND status = :s LIMIT 1', ['e' => $email, 's' => 'active']);
      if ($user !== null) {
        $plain = Crypto::randomToken(32);
        $hash = Crypto::hashToken($plain);
        $now = gmdate('c');
        $exp = gmdate('c', time() + 3600);
        $this->ctx->db()->insert(
          'INSERT INTO password_resets (user_id, token_hash, created_at, expires_at, used_at) VALUES (:uid, :h, :c, :e, NULL)',
          ['uid' => (int)$user['id'], 'h' => $hash, 'c' => $now, 'e' => $exp]
        );
        if ($this->ctx->config()->isDev()) {
          $token = $plain;
        }
      }
    }

    return $this->page('auth/forgot_password', [
      'title' => 'Reset password',
      'sent' => true,
      'token' => $token,
    ], 'layouts/auth');
  }

  public function resetPasswordForm(Request $req, array $params): Response
  {
    $token = (string)($params['token'] ?? '');
    $hash = Crypto::hashToken($token);
    $row = $this->ctx->db()->fetchOne(
      'SELECT id FROM password_resets WHERE token_hash = :h AND used_at IS NULL AND expires_at > :now LIMIT 1',
      ['h' => $hash, 'now' => gmdate('c')]
    );
    if ($row === null) {
      return $this->page('auth/reset_password', ['title' => 'Reset password', 'invalid' => true, 'token' => $token], 'layouts/auth');
    }
    return $this->page('auth/reset_password', ['title' => 'Reset password', 'token' => $token], 'layouts/auth');
  }

  public function resetPasswordSubmit(Request $req, array $params): Response
  {
    $token = (string)($params['token'] ?? '');
    $hash = Crypto::hashToken($token);
    $row = $this->ctx->db()->fetchOne(
      'SELECT id, user_id FROM password_resets WHERE token_hash = :h AND used_at IS NULL AND expires_at > :now LIMIT 1',
      ['h' => $hash, 'now' => gmdate('c')]
    );
    if ($row === null) {
      $this->ctx->session()->flash('error', 'Reset link is invalid or expired.');
      return $this->redirect('/reset-password');
    }

    $password = (string)$req->input('password', '');
    $password2 = (string)$req->input('password_confirm', '');
    if (strlen($password) < 8 || $password !== $password2) {
      $this->ctx->session()->flash('error', 'Password must be at least 8 characters and match confirmation.');
      return $this->redirect('/reset-password/' . urlencode($token));
    }

    $now = gmdate('c');
    $this->ctx->db()->execute('UPDATE users SET password_hash = :h, updated_at = :u WHERE id = :id', [
      'h' => password_hash($password, PASSWORD_DEFAULT),
      'u' => $now,
      'id' => (int)$row['user_id'],
    ]);
    $this->ctx->db()->execute('UPDATE password_resets SET used_at = :u WHERE id = :id', [
      'u' => $now,
      'id' => (int)$row['id'],
    ]);

    $this->ctx->session()->flash('notice', 'Password updated. You can now log in.');
    return $this->redirect('/login');
  }
}

