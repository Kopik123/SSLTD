<?php
declare(strict_types=1);

namespace App\Support;

final class RequestId
{
  private static ?string $id = null;

  public static function set(string $id): void
  {
    $id = trim($id);
    self::$id = $id === '' ? null : $id;
  }

  public static function get(): ?string
  {
    return self::$id;
  }
}

