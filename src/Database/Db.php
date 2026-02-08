<?php
declare(strict_types=1);

namespace App\Database;

use App\Support\Config;
use PDO;
use PDOException;
use RuntimeException;

final class Db
{
  private PDO $pdo;

  private function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  public static function connect(Config $config): self
  {
    $conn = strtolower($config->getString('DB_CONNECTION'));

    try {
      if ($conn === 'mysql') {
        $dsn = sprintf(
          'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
          $config->getString('DB_HOST'),
          $config->getString('DB_PORT'),
          $config->getString('DB_NAME')
        );
        $pdo = new PDO($dsn, $config->getString('DB_USER'), $config->getString('DB_PASS'), [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false,
          // Avoid "Cannot execute queries while other unbuffered queries are active"
          // when running migrations or multiple sequential statements.
          PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]);
        return new self($pdo);
      }

      $dbPath = $config->getString('DB_DATABASE');
      $dir = dirname($dbPath);
      if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
      }

      $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]);
      $pdo->exec('PRAGMA foreign_keys = ON;');
      $pdo->exec('PRAGMA journal_mode = WAL;');
      return new self($pdo);
    } catch (PDOException $e) {
      throw new RuntimeException('DB connection failed: ' . $e->getMessage(), 0, $e);
    }
  }

  public function pdo(): PDO
  {
    return $this->pdo;
  }

  /** @param array<int|string, mixed> $params */
  public function fetchOne(string $sql, array $params = []): ?array
  {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    $stmt->closeCursor();
    return $row === false ? null : $row;
  }

  /** @param array<int|string, mixed> $params */
  public function fetchAll(string $sql, array $params = []): array
  {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $stmt->closeCursor();
    return $rows;
  }

  /** @param array<int|string, mixed> $params */
  public function execute(string $sql, array $params = []): int
  {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    $n = $stmt->rowCount();
    $stmt->closeCursor();
    return $n;
  }

  /** @param array<int|string, mixed> $params */
  public function insert(string $sql, array $params = []): string
  {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();
    return (string)$this->pdo->lastInsertId();
  }
}
