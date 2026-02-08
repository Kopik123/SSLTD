<?php
declare(strict_types=1);

namespace App\Support;

final class Crypto
{
  public static function randomToken(int $bytes = 32): string
  {
    $raw = random_bytes($bytes);
    return self::base64UrlEncode($raw);
  }

  public static function hashToken(string $token): string
  {
    return hash('sha256', $token);
  }

  public static function safeEquals(string $a, string $b): bool
  {
    return hash_equals($a, $b);
  }

  private static function base64UrlEncode(string $raw): string
  {
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
  }
}

