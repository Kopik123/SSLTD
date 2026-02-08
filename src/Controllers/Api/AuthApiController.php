<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Support\Validate;

final class AuthApiController extends Controller
{
  public function login(Request $req, array $params): Response
  {
    $body = $req->json() ?? [];
    $email = Validate::email($body['email'] ?? $req->input('email', ''), 255);
    $password = (string)($body['password'] ?? $req->input('password', ''));

    if ($email === null || trim($password) === '') {
      return Response::json(['error' => 'invalid_request'], 400);
    }

    $u = $this->ctx->db()->fetchOne('SELECT * FROM users WHERE email = :e LIMIT 1', ['e' => $email]);
    if ($u === null || ($u['status'] ?? '') !== 'active') {
      return Response::json(['error' => 'invalid_credentials'], 401);
    }
    if (!password_verify($password, (string)($u['password_hash'] ?? ''))) {
      return Response::json(['error' => 'invalid_credentials'], 401);
    }

    $token = $this->ctx->auth()->issueApiToken((int)$u['id']);
    return Response::json([
      'token' => $token,
      'user' => [
        'id' => (int)$u['id'],
        'role' => (string)$u['role'],
        'name' => (string)$u['name'],
        'email' => (string)$u['email'],
      ],
    ], 200);
  }

  public function register(Request $req, array $params): Response
  {
    $body = $req->json() ?? [];
    $name = Validate::str($body['name'] ?? $req->input('name', ''), 255, true);
    $email = Validate::email($body['email'] ?? $req->input('email', ''), 255);
    $phone = Validate::str($body['phone'] ?? $req->input('phone', ''), 64, false);
    $password = (string)($body['password'] ?? $req->input('password', ''));

    if ($name === null || $email === null || trim($password) === '' || strlen($password) < 8) {
      return Response::json(['error' => 'invalid_request'], 400);
    }

    $existing = $this->ctx->db()->fetchOne('SELECT id FROM users WHERE email = :e LIMIT 1', ['e' => $email]);
    if ($existing !== null) {
      return Response::json(['error' => 'email_taken'], 409);
    }

    $now = gmdate('c');
    $userId = (int)$this->ctx->db()->insert(
      'INSERT INTO users (role, name, email, phone, password_hash, status, created_at, updated_at) VALUES (:r, :n, :e, :p, :h, :s, :c, :u)',
      [
        'r' => 'client',
        'n' => $name,
        'e' => $email,
        'p' => ($phone === null || $phone === '') ? null : $phone,
        'h' => password_hash($password, PASSWORD_DEFAULT),
        's' => 'active',
        'c' => $now,
        'u' => $now,
      ]
    );

    $token = $this->ctx->auth()->issueApiToken($userId);
    return Response::json([
      'token' => $token,
      'user' => [
        'id' => $userId,
        'role' => 'client',
        'name' => $name,
        'email' => $email,
      ],
    ], 201);
  }

  public function me(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }
    return Response::json(['user' => $u], 200);
  }

  public function refresh(Request $req, array $params): Response
  {
    $token = $req->bearerToken();
    if ($token === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $rotated = $this->ctx->auth()->refreshApiToken($token, 30);
    if ($rotated === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    return Response::json([
      'token' => $rotated['token'],
      'user' => $rotated['user'],
    ], 200);
  }
}
