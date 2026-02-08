<?php
declare(strict_types=1);

namespace App\Support;

final class Log
{
  private const MAX_BYTES = 5_000_000;

  /** @param array<string, mixed> $context */
  public static function info(string $event, array $context = []): void
  {
    self::write('INFO', $event, $context);
  }

  /** @param array<string, mixed> $context */
  public static function error(string $event, array $context = []): void
  {
    self::write('ERROR', $event, $context);
  }

  /** @param array<string, mixed> $context */
  private static function write(string $level, string $event, array $context): void
  {
    $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($dir)) {
      @mkdir($dir, 0777, true);
    }
    $path = $dir . DIRECTORY_SEPARATOR . 'app.log';

    // Best-effort rotation to prevent unbounded growth.
    try {
      if (is_file($path)) {
        clearstatcache(true, $path);
        $sz = filesize($path);
        if (is_int($sz) && $sz > self::MAX_BYTES) {
          $rot = $dir . DIRECTORY_SEPARATOR . 'app-' . gmdate('Ymd-His') . '.log';
          @rename($path, $rot);
        }
      }
    } catch (\Throwable $_) {
      // ignore
    }

    $line = sprintf(
      "[%s] %s %s %s\n",
      gmdate('Y-m-d\\TH:i:s\\Z'),
      $level,
      $event,
      $context === [] ? '{}' : json_encode($context, JSON_UNESCAPED_SLASHES)
    );
    $fh = @fopen($path, 'ab');
    if ($fh === false) {
      return;
    }
    @flock($fh, LOCK_EX);
    @fwrite($fh, $line);
    @flock($fh, LOCK_UN);
    @fclose($fh);
  }
}
