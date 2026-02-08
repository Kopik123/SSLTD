<?php
declare(strict_types=1);

namespace App\Support;

final class Config
{
  /** @var array<string, string> */
  private array $values;

  /** @param array<string, string> $values */
  private function __construct(array $values)
  {
    $this->values = $values;
  }

  /** @param array<string, string> $defaults */
  public static function fromEnv(array $defaults): self
  {
    $values = [];
    foreach ($defaults as $key => $default) {
      $env = getenv($key);
      $values[$key] = ($env === false || $env === '') ? $default : $env;
    }
    return new self($values);
  }

  public function getString(string $key): string
  {
    return $this->values[$key] ?? '';
  }

  public function getInt(string $key): int
  {
    return (int)($this->values[$key] ?? '0');
  }

  public function isDebug(): bool
  {
    $v = strtolower($this->getString('APP_DEBUG'));
    return $v === '1' || $v === 'true' || $v === 'yes' || $v === 'on';
  }

  public function isDev(): bool
  {
    $env = strtolower($this->getString('APP_ENV'));
    return $env !== 'prod' && $env !== 'production';
  }
}
