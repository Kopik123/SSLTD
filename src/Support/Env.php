<?php
declare(strict_types=1);

namespace App\Support;

final class Env
{
  public static function load(string $path): void
  {
    if (!is_file($path)) {
      return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
      return;
    }

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || str_starts_with($line, '#')) {
        continue;
      }

      $pos = strpos($line, '=');
      if ($pos === false) {
        continue;
      }

      $key = trim(substr($line, 0, $pos));
      $value = trim(substr($line, $pos + 1));

      if ($key === '') {
        continue;
      }

      if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
          (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
        $value = substr($value, 1, -1);
      }

      putenv($key . '=' . $value);
      $_ENV[$key] = $value;
      $_SERVER[$key] = $value;
    }
  }
}

