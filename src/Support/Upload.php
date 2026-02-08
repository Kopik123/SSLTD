<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Upload
{
  /**
   * Normalize PHP's weird `$_FILES` structure into a list of file arrays.
   *
   * @param array<string, mixed> $filesEntry e.g. $_FILES['attachments']
   * @return array<int, array{name:string,type:string,tmp_name:string,error:int,size:int}>
   */
  public static function normalize(array $filesEntry): array
  {
    // Single file input
    if (isset($filesEntry['name']) && is_string($filesEntry['name'])) {
      return [[
        'name' => (string)($filesEntry['name'] ?? ''),
        'type' => (string)($filesEntry['type'] ?? ''),
        'tmp_name' => (string)($filesEntry['tmp_name'] ?? ''),
        'error' => (int)($filesEntry['error'] ?? UPLOAD_ERR_NO_FILE),
        'size' => (int)($filesEntry['size'] ?? 0),
      ]];
    }

    // Multiple files input
    $names = $filesEntry['name'] ?? [];
    if (!is_array($names)) {
      return [];
    }
    $out = [];
    $count = count($names);
    for ($i = 0; $i < $count; $i++) {
      $out[] = [
        'name' => (string)($filesEntry['name'][$i] ?? ''),
        'type' => (string)($filesEntry['type'][$i] ?? ''),
        'tmp_name' => (string)($filesEntry['tmp_name'][$i] ?? ''),
        'error' => (int)($filesEntry['error'][$i] ?? UPLOAD_ERR_NO_FILE),
        'size' => (int)($filesEntry['size'][$i] ?? 0),
      ];
    }
    return $out;
  }

  public static function detectMime(string $tmpPath): string
  {
    if (!is_file($tmpPath)) {
      return '';
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
      return '';
    }
    $mime = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);
    return is_string($mime) ? $mime : '';
  }

  public static function extForMime(string $mime): ?string
  {
    $map = [
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'application/pdf' => 'pdf',
    ];
    return $map[$mime] ?? null;
  }

  /**
   * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
   * @param array<int, string> $allowedMimes
   * @return array{storage_path:string,mime_type:string,original_name:string,size_bytes:int}
   */
  public static function save(array $file, string $destDir, array $allowedMimes, int $maxBytes): array
  {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      throw new RuntimeException('Upload failed with error: ' . (string)($file['error'] ?? 'unknown'));
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
      throw new RuntimeException('Invalid upload size.');
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    $mime = self::detectMime($tmp);
    if ($mime === '' || !in_array($mime, $allowedMimes, true)) {
      throw new RuntimeException('Unsupported file type.');
    }
    $ext = self::extForMime($mime);
    if ($ext === null) {
      throw new RuntimeException('Unsupported file type.');
    }

    if (!is_dir($destDir)) {
      @mkdir($destDir, 0777, true);
    }

    $name = Uuid::v4() . '.' . $ext;
    $destPath = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($tmp, $destPath)) {
      throw new RuntimeException('Failed to store uploaded file.');
    }

    // Best-effort: re-encode images to reduce risk from exotic payloads and strip metadata.
    try {
      if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
        $im = @imagecreatefromjpeg($destPath);
        if ($im !== false) {
          @imagejpeg($im, $destPath, 85);
          @imagedestroy($im);
        }
      } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng') && function_exists('imagepng')) {
        $im = @imagecreatefrompng($destPath);
        if ($im !== false) {
          // Keep alpha.
          @imagealphablending($im, false);
          @imagesavealpha($im, true);
          @imagepng($im, $destPath, 6);
          @imagedestroy($im);
        }
      }
    } catch (\Throwable $_) {
      // keep original bytes if re-encode fails
    }

    return [
      'storage_path' => $destPath,
      'mime_type' => $mime,
      'original_name' => (string)($file['name'] ?? 'upload.' . $ext),
      'size_bytes' => $size,
    ];
  }
}
