<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Context;
use App\Http\Response;
use App\Http\View;

abstract class Controller
{
  protected Context $ctx;
  protected View $view;

  public function __construct(Context $ctx)
  {
    $this->ctx = $ctx;
    $this->view = new View($ctx);
  }

  /** @param array<string, mixed> $data */
  protected function page(string $template, array $data, string $layout): Response
  {
    return Response::html($this->view->render($template, $data, $layout));
  }

  protected function redirect(string $to): Response
  {
    if (str_starts_with($to, '/')) {
      $base = $this->ctx->basePath();
      if ($base !== '') {
        return Response::redirect($to === '/' ? ($base . '/') : ($base . $to));
      }
    }
    return Response::redirect($to);
  }

  /** @param array<string, mixed> $meta */
  protected function audit(string $action, string $entityType, ?int $entityId, array $meta = []): void
  {
    try {
      $u = $this->ctx->auth()->user();
      $this->ctx->db()->insert(
        'INSERT INTO audit_log (actor_user_id, action, entity_type, entity_id, meta_json, created_at)
         VALUES (:aid, :ac, :et, :eid, :mj, :c)',
        [
          'aid' => $u !== null ? (int)($u['id'] ?? 0) : null,
          'ac' => $action,
          'et' => $entityType,
          'eid' => $entityId !== null ? $entityId : null,
          'mj' => $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_SLASHES),
          'c' => gmdate('c'),
        ]
      );
    } catch (\Throwable $_) {
      // audit must never break the main user flow
    }
  }
}
