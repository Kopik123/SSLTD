<?php
declare(strict_types=1);

namespace App\Support;

final class Validate
{
  public static function str(mixed $v, int $maxLen, bool $required = true): ?string
  {
    $s = is_string($v) ? $v : (is_numeric($v) ? (string)$v : '');
    $s = trim($s);
    if ($s === '') {
      return $required ? null : null;
    }
    if ($maxLen > 0 && mb_strlen($s) > $maxLen) {
      $s = mb_substr($s, 0, $maxLen);
    }
    return $s;
  }

  public static function email(mixed $v, int $maxLen = 255): ?string
  {
    $s = self::str($v, $maxLen, true);
    if ($s === null) {
      return null;
    }
    if (filter_var($s, FILTER_VALIDATE_EMAIL) === false) {
      return null;
    }
    return $s;
  }

  /** @param list<string> $allowed */
  public static function enum(mixed $v, array $allowed, bool $required = true): ?string
  {
    $s = self::str($v, 64, $required);
    if ($s === null) {
      return null;
    }
    $s = strtolower($s);
    return in_array($s, $allowed, true) ? $s : null;
  }

  public static function intRange(mixed $v, int $min, int $max): ?int
  {
    if (is_int($v)) {
      $i = $v;
    } else if (is_string($v) && $v !== '') {
      $i = (int)$v;
    } else if (is_float($v)) {
      $i = (int)$v;
    } else {
      return null;
    }
    if ($i < $min || $i > $max) {
      return null;
    }
    return $i;
  }

  public static function bool01(mixed $v): int
  {
    if ($v === 1 || $v === '1' || $v === true || $v === 'true' || $v === 'on') {
      return 1;
    }
    return 0;
  }
}

