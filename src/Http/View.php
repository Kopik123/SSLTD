<?php
declare(strict_types=1);

namespace App\Http;

use App\Context;
use RuntimeException;

final class View
{
  private Context $ctx;

  public function __construct(Context $ctx)
  {
    $this->ctx = $ctx;
  }

  /** @param array<string, mixed> $data */
  public function render(string $template, array $data = [], ?string $layout = null): string
  {
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR;

    $templatePath = $base . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';
    if (!is_file($templatePath)) {
      throw new RuntimeException('View not found: ' . $template);
    }

    $ctx = $this->ctx; // available in templates
    extract($data, EXTR_SKIP);

    ob_start();
    require $templatePath;
    $content = (string)ob_get_clean();

    if ($layout === null) {
      return $content;
    }

    $layoutPath = $base . str_replace('/', DIRECTORY_SEPARATOR, $layout) . '.php';
    if (!is_file($layoutPath)) {
      throw new RuntimeException('Layout not found: ' . $layout);
    }

    ob_start();
    require $layoutPath;
    return (string)ob_get_clean();
  }
}

