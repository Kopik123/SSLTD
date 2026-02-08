<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Support\Upload;

final class UploadsApiController extends Controller
{
  public function create(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $ownerType = strtolower(trim((string)$req->input('owner_type', '')));
    $ownerId = (int)$req->input('owner_id', 0);
    $stage = strtolower(trim((string)$req->input('stage', '')));
    $clientVisible = (string)$req->input('client_visible', '0') === '1' ? 1 : 0;

    if ($ownerType !== 'project' && $ownerType !== 'quote_request') {
      return Response::json(['error' => 'invalid_owner_type'], 400);
    }
    if ($ownerId <= 0) {
      return Response::json(['error' => 'invalid_owner_id'], 400);
    }

    $allowedStages = ['', 'before', 'during', 'after', 'doc'];
    if (!in_array($stage, $allowedStages, true)) {
      return Response::json(['error' => 'invalid_stage'], 400);
    }

    if (!$this->canAccessOwner($ownerType, $ownerId, $u)) {
      return Response::json(['error' => 'forbidden'], 403);
    }

    $files = $req->files();
    $entry = null;
    foreach (['file', 'files', 'attachments'] as $key) {
      if (isset($files[$key]) && is_array($files[$key])) {
        $entry = $files[$key];
        break;
      }
    }
    if ($entry === null) {
      return Response::json(['error' => 'file_required'], 400);
    }

    $normalized = Upload::normalize($entry);
    if ($normalized === []) {
      return Response::json(['error' => 'file_required'], 400);
    }

    $normalized = array_slice($normalized, 0, 10); // hard cap
    $base = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
    $destDir = $base . DIRECTORY_SEPARATOR . ($ownerType === 'project' ? 'projects' : 'quote_requests') . DIRECTORY_SEPARATOR . (string)$ownerId;

    $now = gmdate('c');
    $created = [];
    $failed = 0;

    foreach ($normalized as $file) {
      if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        continue;
      }
      try {
        $saved = Upload::save($file, $destDir, ['image/jpeg', 'image/png', 'application/pdf'], 10 * 1024 * 1024);
        $id = (int)$this->ctx->db()->insert(
          'INSERT INTO uploads (owner_type, owner_id, storage_path, original_name, mime_type, size_bytes, uploaded_by_user_id, stage, client_visible, created_at)
           VALUES (:ot, :oid, :sp, :on, :mt, :sz, :ub, :st, :cv, :c)',
          [
            'ot' => $ownerType,
            'oid' => $ownerId,
            'sp' => $saved['storage_path'],
            'on' => $saved['original_name'],
            'mt' => $saved['mime_type'],
            'sz' => $saved['size_bytes'],
            'ub' => (int)$u['id'],
            'st' => $stage === '' ? null : $stage,
            'cv' => $clientVisible,
            'c' => $now,
          ]
        );
        $created[] = $id;
      } catch (\Throwable $e) {
        $failed++;
      }
    }

    if ($created === []) {
      return Response::json(['error' => 'upload_failed'], 400);
    }

    return Response::json(['ids' => $created, 'failed' => $failed], 201);
  }

  /** @param array<string, mixed> $user */
  private function canAccessOwner(string $ownerType, int $ownerId, array $user): bool
  {
    $role = (string)($user['role'] ?? '');
    $uid = (int)($user['id'] ?? 0);

    if ($role === 'admin' || $role === 'pm') {
      return true;
    }

    if ($ownerType === 'project') {
      if ($role === 'client') {
        $p = $this->ctx->db()->fetchOne('SELECT client_user_id FROM projects WHERE id = :id LIMIT 1', ['id' => $ownerId]);
        return $p !== null && (int)($p['client_user_id'] ?? 0) === $uid;
      }
      $mem = $this->ctx->db()->fetchOne(
        'SELECT 1 AS ok FROM project_members WHERE project_id = :pid AND user_id = :uid LIMIT 1',
        ['pid' => $ownerId, 'uid' => $uid]
      );
      return $mem !== null;
    }

    if ($ownerType === 'quote_request') {
      $qr = $this->ctx->db()->fetchOne('SELECT client_user_id FROM quote_requests WHERE id = :id LIMIT 1', ['id' => $ownerId]);
      if ($qr === null) {
        return false;
      }
      return $role === 'client' && (int)($qr['client_user_id'] ?? 0) === $uid;
    }

    return false;
  }
}

