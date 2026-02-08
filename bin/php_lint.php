<?php
declare(strict_types=1);

/**
 * Recursively runs `php -l` on project PHP files.
 *
 * Usage:
 *   php bin/php_lint.php
 */

$root = realpath(__DIR__ . '/..');
if ($root === false) {
  fwrite(STDERR, "Unable to locate project root.\n");
  exit(2);
}

$it = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$php = PHP_BINARY;
$fail = 0;

foreach ($it as $file) {
  /** @var SplFileInfo $file */
  $path = $file->getPathname();
  if (substr($path, -4) !== '.php') {
    continue;
  }
  if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
    continue;
  }

  $cmd = escapeshellarg($php) . ' -l ' . escapeshellarg($path);
  exec($cmd, $out, $code);
  if ($code !== 0) {
    $fail++;
    fwrite(STDERR, "Lint failed: " . $path . "\n");
  }
}

if ($fail === 0) {
  fwrite(STDOUT, "PHP lint OK\n");
  exit(0);
}

fwrite(STDERR, "PHP lint failed: " . $fail . " file(s)\n");
exit(1);

