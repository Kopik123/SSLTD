<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Support\Validate;

final class QuoteRequestsApiController extends Controller
{
  public function create(Request $req, array $params): Response
  {
    $body = $req->json() ?? [];
    $name = Validate::str($body['name'] ?? '', 255, true);
    $email = Validate::email($body['email'] ?? '', 255);
    $phone = Validate::str($body['phone'] ?? '', 64, false);
    $address = Validate::str($body['address'] ?? '', 512, true);
    $scope = $body['scope'] ?? [];
    $desc = Validate::str($body['description'] ?? '', 5000, false);
    $preferred = $body['preferred_dates'] ?? [];

    if ($name === null || $email === null || $address === null) {
      return Response::json(['error' => 'invalid_request'], 400);
    }

    $scopeList = [];
    if (is_array($scope)) {
      foreach ($scope as $s) {
        $v = Validate::str($s, 64, false);
        if ($v !== null && $v !== '') $scopeList[] = $v;
      }
    }
    if ($scopeList === []) {
      return Response::json(['error' => 'scope_required'], 400);
    }

    $preferredList = [];
    if (is_array($preferred)) {
      foreach ($preferred as $d) {
        $v = Validate::str($d, 64, false);
        if ($v !== null && $v !== '') $preferredList[] = $v;
      }
    }

    $now = gmdate('c');
    $leadId = (int)$this->ctx->db()->insert(
      'INSERT INTO quote_requests (status, client_user_id, name, email, phone, address, scope_json, description, preferred_dates_json, assigned_pm_user_id, service_area_ok, created_at, updated_at)
       VALUES (:st, NULL, :n, :e, :p, :a, :scope, :d, :pd, NULL, 1, :c, :u)',
      [
        'st' => 'quote_requested',
        'n' => $name,
        'e' => $email,
        'p' => ($phone === null || $phone === '') ? null : $phone,
        'a' => $address,
        'scope' => json_encode($scopeList, JSON_UNESCAPED_SLASHES),
        'd' => ($desc === null || $desc === '') ? null : $desc,
        'pd' => $preferredList === [] ? null : json_encode($preferredList, JSON_UNESCAPED_SLASHES),
        'c' => $now,
        'u' => $now,
      ]
    );

    return Response::json(['id' => $leadId, 'status' => 'quote_requested'], 201);
  }

  public function list(Request $req, array $params): Response
  {
    $page = (int)$req->query('page', '1');
    if ($page <= 0) $page = 1;
    $perPage = (int)$req->query('per_page', '50');
    if ($perPage <= 0) $perPage = 50;
    if ($perPage > 200) $perPage = 200;
    $offset = ($page - 1) * $perPage;
    if ($offset < 0) $offset = 0;
    if ($offset > 100000) $offset = 100000;

    $rows = $this->ctx->db()->fetchAll(
      'SELECT id, status, name, email, phone, address, assigned_pm_user_id, created_at, updated_at
       FROM quote_requests
       ORDER BY created_at DESC
       LIMIT ' . ($perPage + 1) . ' OFFSET ' . $offset
    );
    $hasMore = false;
    if (count($rows) > $perPage) {
      $hasMore = true;
      $rows = array_slice($rows, 0, $perPage);
    }
    return Response::json(['items' => $rows, 'meta' => ['page' => $page, 'per_page' => $perPage, 'has_more' => $hasMore]], 200);
  }

  public function get(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT * FROM quote_requests WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) {
      return Response::json(['error' => 'not_found'], 404);
    }
    return Response::json(['item' => $row], 200);
  }
}
