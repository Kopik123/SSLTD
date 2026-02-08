<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Support\Upload;

final class UploadsController extends Controller
{
  /** @return list<string> */
  private function allowedStages(): array
  {
    return ['', 'before', 'during', 'after', 'doc'];
  }

  public function download(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) {
      return Response::html('<h1>404</h1><p>File not found.</p>', 404);
    }

    $u = $this->ctx->auth()->user();
    if ($u === null) {
      return Response::html('<h1>401</h1><p>Unauthorized.</p>', 401);
    }

    $role = (string)($u['role'] ?? '');
    $upload = $this->ctx->db()->fetchOne('SELECT * FROM uploads WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($upload === null) {
      return Response::html('<h1>404</h1><p>File not found.</p>', 404);
    }

    if ($role !== 'admin' && $role !== 'pm') {
      // Client can download only client-visible files belonging to their lead/project, or their own uploads.
      if ($role !== 'client' || !$this->canClientDownload($upload, $u)) {
        return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
      }
    }

    $storagePath = (string)($upload['storage_path'] ?? '');
    if ($storagePath === '') {
      return Response::html('<h1>404</h1><p>File not found.</p>', 404);
    }

    $uploadsRoot = realpath(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads');
    $real = realpath($storagePath);
    if (!is_string($uploadsRoot) || $uploadsRoot === false || !is_string($real) || $real === false) {
      return Response::html('<h1>404</h1><p>File not found.</p>', 404);
    }

    // Prevent path traversal / arbitrary file reads.
    if (strncmp($real, $uploadsRoot, strlen($uploadsRoot)) !== 0) {
      return Response::html('<h1>404</h1><p>File not found.</p>', 404);
    }

    if (!is_file($real)) {
      return Response::html('<h1>404</h1><p>File not found.</p>', 404);
    }

    $mime = (string)($upload['mime_type'] ?? 'application/octet-stream');
    $name = $this->safeDownloadName((string)($upload['original_name'] ?? 'download'));
    $size = filesize($real);
    $headers = [
      'Content-Type' => $mime,
      'Content-Disposition' => 'attachment; filename="' . $name . '"',
      'Cache-Control' => 'private, max-age=0, no-cache, no-store, must-revalidate',
      'Pragma' => 'no-cache',
      'Expires' => '0',
    ];
    if (is_int($size) && $size >= 0) {
      $headers['Content-Length'] = (string)$size;
    }

    return Response::stream(
      static function () use ($real): void {
        $fp = fopen($real, 'rb');
        if ($fp === false) {
          return;
        }
        fpassthru($fp);
        fclose($fp);
      },
      $headers,
      200
    );
  }

  private function safeDownloadName(string $name): string
  {
    $name = trim($name);
    $name = preg_replace('/[\\r\\n]+/', ' ', $name) ?? $name;
    $name = str_replace(['"', '\\', '/'], '_', $name);
    $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', $name) ?? $name;
    $name = trim($name);
    if ($name === '') {
      return 'download';
    }
    if (strlen($name) > 150) {
      $name = substr($name, -150);
    }
    return $name;
  }

  public function uploadToLead(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null) {
      return Response::html('<h1>401</h1><p>Unauthorized.</p>', 401);
    }
    $role = (string)($u['role'] ?? '');
    if ($role !== 'admin' && $role !== 'pm') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }

    $leadId = (int)($params['id'] ?? 0);
    $lead = $this->ctx->db()->fetchOne('SELECT id FROM quote_requests WHERE id = :id LIMIT 1', ['id' => $leadId]);
    if ($lead === null) {
      return Response::html('<h1>404</h1><p>Lead not found.</p>', 404);
    }

    $stage = strtolower(trim((string)$req->input('stage', 'doc')));
    if (!in_array($stage, $this->allowedStages(), true)) {
      $this->ctx->session()->flash('error', 'Invalid stage.');
      return $this->redirect('/app/leads/' . $leadId);
    }

    $clientVisible = ((string)$req->input('client_visible', '0') === '1') ? 1 : 0;

    $files = $req->files();
    $entry = $files['file'] ?? null;
    if (!is_array($entry)) {
      $this->ctx->session()->flash('error', 'File is required.');
      return $this->redirect('/app/leads/' . $leadId);
    }

    try {
      $base = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
      $destDir = $base . DIRECTORY_SEPARATOR . 'quote_requests' . DIRECTORY_SEPARATOR . (string)$leadId;
      $saved = Upload::save($entry, $destDir, ['image/jpeg', 'image/png', 'application/pdf'], 10 * 1024 * 1024);
      $now = gmdate('c');
      $id = (int)$this->ctx->db()->insert(
        'INSERT INTO uploads (owner_type, owner_id, storage_path, original_name, mime_type, size_bytes, uploaded_by_user_id, stage, client_visible, created_at)
         VALUES (:ot, :oid, :sp, :on, :mt, :sz, :ub, :st, :cv, :c)',
        [
          'ot' => 'quote_request',
          'oid' => $leadId,
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
      $this->audit('upload_created', 'quote_request', $leadId, ['upload_id' => $id, 'client_visible' => $clientVisible, 'stage' => $stage]);
      $this->ctx->session()->flash('notice', 'File uploaded.');
    } catch (\Throwable $e) {
      $this->ctx->session()->flash('error', 'Upload failed.');
    }

    return $this->redirect('/app/leads/' . $leadId);
  }

  public function uploadToProject(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null) {
      return Response::html('<h1>401</h1><p>Unauthorized.</p>', 401);
    }
    $role = (string)($u['role'] ?? '');
    if ($role !== 'admin' && $role !== 'pm') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }

    $projectId = (int)($params['id'] ?? 0);
    $p = $this->ctx->db()->fetchOne('SELECT id FROM projects WHERE id = :id LIMIT 1', ['id' => $projectId]);
    if ($p === null) {
      return Response::html('<h1>404</h1><p>Project not found.</p>', 404);
    }

    $stage = strtolower(trim((string)$req->input('stage', 'doc')));
    if (!in_array($stage, $this->allowedStages(), true)) {
      $this->ctx->session()->flash('error', 'Invalid stage.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $clientVisible = ((string)$req->input('client_visible', '0') === '1') ? 1 : 0;

    $files = $req->files();
    $entry = $files['file'] ?? null;
    if (!is_array($entry)) {
      $this->ctx->session()->flash('error', 'File is required.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    try {
      $base = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
      $destDir = $base . DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR . (string)$projectId;
      $saved = Upload::save($entry, $destDir, ['image/jpeg', 'image/png', 'application/pdf'], 10 * 1024 * 1024);
      $now = gmdate('c');
      $id = (int)$this->ctx->db()->insert(
        'INSERT INTO uploads (owner_type, owner_id, storage_path, original_name, mime_type, size_bytes, uploaded_by_user_id, stage, client_visible, created_at)
         VALUES (:ot, :oid, :sp, :on, :mt, :sz, :ub, :st, :cv, :c)',
        [
          'ot' => 'project',
          'oid' => $projectId,
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
      $this->audit('upload_created', 'project', $projectId, ['upload_id' => $id, 'client_visible' => $clientVisible, 'stage' => $stage]);
      $this->ctx->session()->flash('notice', 'File uploaded.');
    } catch (\Throwable $e) {
      $this->ctx->session()->flash('error', 'Upload failed.');
    }

    return $this->redirect('/app/projects/' . $projectId);
  }

  public function setVisibility(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null) {
      return Response::html('<h1>401</h1><p>Unauthorized.</p>', 401);
    }
    $role = (string)($u['role'] ?? '');
    if ($role !== 'admin' && $role !== 'pm') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }

    $id = (int)($params['id'] ?? 0);
    $upload = $this->ctx->db()->fetchOne('SELECT id, owner_type, owner_id, client_visible FROM uploads WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($upload === null) {
      return Response::html('<h1>404</h1><p>File not found.</p>', 404);
    }

    $cv = ((string)$req->input('client_visible', '0') === '1') ? 1 : 0;
    $before = (int)($upload['client_visible'] ?? 0);
    $this->ctx->db()->execute('UPDATE uploads SET client_visible = :cv WHERE id = :id', [
      'cv' => $cv,
      'id' => $id,
    ]);

    $this->audit('upload_visibility_update', (string)($upload['owner_type'] ?? ''), (int)($upload['owner_id'] ?? 0), [
      'upload_id' => $id,
      'before' => $before,
      'after' => $cv,
    ]);

    $back = trim((string)$req->input('back', ''));
    if ($back !== '' && str_starts_with($back, '/app/')) {
      return $this->redirect($back);
    }

    return $this->redirect('/app');
  }

  /** @param array<string, mixed> $upload
   *  @param array<string, mixed> $user
   */
  private function canClientDownload(array $upload, array $user): bool
  {
    $uid = (int)($user['id'] ?? 0);
    if ($uid <= 0) {
      return false;
    }

    if ((int)($upload['uploaded_by_user_id'] ?? 0) === $uid) {
      return true;
    }

    if ((int)($upload['client_visible'] ?? 0) !== 1) {
      return false;
    }

    $ownerType = (string)($upload['owner_type'] ?? '');
    $ownerId = (int)($upload['owner_id'] ?? 0);
    if ($ownerId <= 0) {
      return false;
    }

    if ($ownerType === 'quote_request') {
      $qr = $this->ctx->db()->fetchOne('SELECT client_user_id FROM quote_requests WHERE id = :id LIMIT 1', ['id' => $ownerId]);
      return $qr !== null && (int)($qr['client_user_id'] ?? 0) === $uid;
    }
    if ($ownerType === 'project') {
      $p = $this->ctx->db()->fetchOne('SELECT client_user_id FROM projects WHERE id = :id LIMIT 1', ['id' => $ownerId]);
      return $p !== null && (int)($p['client_user_id'] ?? 0) === $uid;
    }

    return false;
  }
}
