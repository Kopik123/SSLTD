<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Support\Upload;
use App\Support\Validate;

final class PublicController extends Controller
{
  public function home(Request $req, array $params): Response
  {
    return $this->page('public/home', ['title' => 'S&S LTD'], 'layouts/public');
  }

  public function about(Request $req, array $params): Response
  {
    return $this->page('public/about', ['title' => 'About'], 'layouts/public');
  }

  public function services(Request $req, array $params): Response
  {
    return $this->page('public/services', ['title' => 'Services'], 'layouts/public');
  }

  public function gallery(Request $req, array $params): Response
  {
    return $this->page('public/gallery', ['title' => 'Gallery'], 'layouts/public');
  }

  public function contact(Request $req, array $params): Response
  {
    return $this->page('public/contact', ['title' => 'Contact'], 'layouts/public');
  }

  public function privacy(Request $req, array $params): Response
  {
    return $this->page('public/privacy', ['title' => 'Privacy Policy'], 'layouts/public');
  }

  public function terms(Request $req, array $params): Response
  {
    return $this->page('public/terms', ['title' => 'Terms of Service'], 'layouts/public');
  }

  public function quoteRequestForm(Request $req, array $params): Response
  {
    $mode = strtolower(trim((string)$req->query('mode', '')));
    if ($mode !== 'simple' && $mode !== 'advanced') {
      $mode = '';
    }

    return $this->page('public/quote_request', [
      'title' => 'Request a Quote',
      'mode' => $mode,
    ], 'layouts/public');
  }

  public function quoteRequestSubmit(Request $req, array $params): Response
  {
    $mode = strtolower(trim((string)$req->input('mode', 'advanced')));
    if ($mode !== 'simple' && $mode !== 'advanced') {
      $mode = 'advanced';
    }

    $name = Validate::str($req->input('name', ''), 255, true);
    $email = Validate::email($req->input('email', ''), 255);
    $phone = Validate::str($req->input('phone', ''), 64, false);
    $desc = Validate::str($req->input('description', ''), 5000, false);
    $consent = Validate::bool01($req->input('consent_privacy', '0'));

    $errors = [];
    if ($name === null) $errors[] = 'Name is required.';
    if ($email === null) $errors[] = 'Valid email is required.';
    if ($consent !== 1) $errors[] = 'Privacy consent is required.';

    $address = '';
    $scopeList = [];
    $preferredList = [];

    if ($mode === 'simple') {
      if ($desc === null || mb_strlen($desc) < 10) $errors[] = 'Please describe your request (at least 10 characters).';
      $address = 'TBD';
      $scopeList = ['simple'];
    } else {
      $address = Validate::str($req->input('address', ''), 512, true) ?? '';
      $scope = $req->input('scope', []);
      $preferred = $req->input('preferred_dates', []);

      if ($address === '') $errors[] = 'Address is required.';

      if (is_array($scope)) {
        foreach ($scope as $s) {
          if (is_string($s) && $s !== '') $scopeList[] = $s;
        }
      } elseif (is_string($scope) && $scope !== '') {
        $scopeList[] = $scope;
      }
      if ($scopeList === []) $errors[] = 'Select at least one scope item.';

      if (is_array($preferred)) {
        foreach ($preferred as $d) {
          if (!is_string($d)) continue;
          $d = trim($d);
          if ($d !== '') $preferredList[] = $d;
        }
      }
    }

    if ($errors !== []) {
      $this->ctx->session()->flash('error', implode(' ', $errors));
      return $this->redirect('/quote-request?mode=' . urlencode($mode));
    }

    $now = gmdate('c');
    $clientUserId = null;
    $u = $this->ctx->auth()->user();
    if ($u !== null && ($u['role'] ?? '') === 'client') {
      $clientUserId = (int)$u['id'];
    }

    $leadId = (int)$this->ctx->db()->insert(
      'INSERT INTO quote_requests (status, client_user_id, name, email, phone, address, scope_json, description, preferred_dates_json, assigned_pm_user_id, service_area_ok, created_at, updated_at)
       VALUES (:st, :cuid, :n, :e, :p, :a, :scope, :d, :pd, :pm, :ok, :c, :u)',
      [
        'st' => 'quote_requested',
        'cuid' => $clientUserId,
        'n' => $name,
        'e' => $email,
        'p' => ($phone === null || $phone === '') ? null : $phone,
        'a' => $address,
        'scope' => json_encode($scopeList, JSON_UNESCAPED_SLASHES),
        'd' => ($desc === null || $desc === '') ? null : $desc,
        'pd' => $preferredList === [] ? null : json_encode($preferredList, JSON_UNESCAPED_SLASHES),
        'pm' => null,
        'ok' => 1,
        'c' => $now,
        'u' => $now,
      ]
    );

    // Attachments (optional)
    $files = $req->files();
    if (isset($files['attachments']) && is_array($files['attachments'])) {
      $normalized = Upload::normalize($files['attachments']);
      $normalized = array_slice($normalized, 0, 10); // hard cap
      $base = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
      $destDir = $base . DIRECTORY_SEPARATOR . 'quote_requests' . DIRECTORY_SEPARATOR . (string)$leadId;

      foreach ($normalized as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
          continue;
        }
        try {
          $saved = Upload::save($file, $destDir, ['image/jpeg', 'image/png', 'application/pdf'], 10 * 1024 * 1024);
          $this->ctx->db()->insert(
            'INSERT INTO uploads (owner_type, owner_id, storage_path, original_name, mime_type, size_bytes, uploaded_by_user_id, stage, client_visible, created_at)
             VALUES (:ot, :oid, :sp, :on, :mt, :sz, :ub, :st, :cv, :c)',
            [
              'ot' => 'quote_request',
              'oid' => $leadId,
              'sp' => $saved['storage_path'],
              'on' => $saved['original_name'],
              'mt' => $saved['mime_type'],
              'sz' => $saved['size_bytes'],
              'ub' => $clientUserId,
              'st' => null,
              'cv' => 0,
              'c' => gmdate('c'),
            ]
          );
        } catch (\Throwable $e) {
          // Best-effort: keep the lead even if an attachment fails.
          $this->ctx->session()->flash('notice', 'Lead created, but one or more attachments failed to upload.');
        }
      }
    }

    return $this->page('public/quote_request_success', ['title' => 'Request Sent', 'leadId' => $leadId], 'layouts/public');
  }
}
