<?php
declare(strict_types=1);

/**
 * Checks that full_todos.md contains no unchecked markdown tasks ("- [ ] ").
 *
 * Usage:
 *   C:\xampp\php\php.exe bin\check_full_todos_done.php
 */

$path = __DIR__ . '/../full_todos.md';
$raw = @file_get_contents($path);
if (!is_string($raw)) {
  fwrite(STDERR, "Unable to read full_todos.md\n");
  exit(2);
}

if (strpos($raw, '- [ ]') !== false) {
  fwrite(STDERR, "FAIL: found unchecked tasks in full_todos.md\n");
  exit(1);
}

fwrite(STDOUT, "OK: no unchecked tasks in full_todos.md\n");
exit(0);

