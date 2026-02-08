<?php
declare(strict_types=1);

/**
 * Prints any unchecked markdown tasks ("- [ ]") from full_todos.md with line numbers.
 *
 * Usage:
 *   C:\xampp\php\php.exe bin\find_unchecked_todos.php
 */

$path = __DIR__ . '/../full_todos.md';
$raw = @file_get_contents($path);
if (!is_string($raw)) {
  fwrite(STDERR, "Unable to read full_todos.md\n");
  exit(2);
}

$lines = preg_split("/\\r\\n|\\n|\\r/", $raw) ?: [];
$found = false;
for ($i = 0; $i < count($lines); $i++) {
  $line = $lines[$i];
  if (strpos($line, '- [ ]') !== false) {
    $found = true;
    fwrite(STDOUT, ($i + 1) . ':' . $line . "\n");
  }
}

exit($found ? 1 : 0);

