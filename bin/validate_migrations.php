<?php
declare(strict_types=1);

/**
 * Validates that mysql.sql is up-to-date with migration files.
 *
 * This script checks that the mysql.sql file contains all the schema
 * defined in the migration files. It's used in CI to ensure mysql.sql
 * is kept in sync when migrations are added or modified.
 *
 * Usage:
 *   php bin/validate_migrations.php
 */

$root = realpath(__DIR__ . '/..');
if ($root === false) {
  fwrite(STDERR, "Unable to locate project root.\n");
  exit(2);
}

$mysqlSqlPath = $root . '/mysql.sql';
$migrationsDir = $root . '/database/migrations';

if (!file_exists($mysqlSqlPath)) {
  fwrite(STDERR, "ERROR: mysql.sql not found at: {$mysqlSqlPath}\n");
  exit(1);
}

if (!is_dir($migrationsDir)) {
  fwrite(STDERR, "ERROR: migrations directory not found at: {$migrationsDir}\n");
  exit(1);
}

$mysqlContent = file_get_contents($mysqlSqlPath);
if ($mysqlContent === false) {
  fwrite(STDERR, "ERROR: Unable to read mysql.sql\n");
  exit(1);
}

// Get all migration files
$migrationFiles = glob($migrationsDir . '/*.sql') ?: [];
sort($migrationFiles);

if (count($migrationFiles) === 0) {
  fwrite(STDERR, "ERROR: No migration files found in {$migrationsDir}\n");
  exit(1);
}

// Extract table names from migrations
// This is a simple check - we look for CREATE TABLE statements
$expectedTables = [];
foreach ($migrationFiles as $file) {
  $content = file_get_contents($file);
  if ($content === false) {
    continue;
  }
  
  // Match CREATE TABLE statements
  if (preg_match_all('/CREATE TABLE(?:\s+IF NOT EXISTS)?\s+`?([a-z_]+)`?/i', $content, $matches)) {
    foreach ($matches[1] as $table) {
      $expectedTables[$table] = true;
    }
  }
}

// Check that mysql.sql contains all expected tables
$missingTables = [];
foreach (array_keys($expectedTables) as $table) {
  // Look for the table definition in mysql.sql
  $pattern = '/CREATE TABLE(?:\s+IF NOT EXISTS)?\s+`?' . preg_quote($table, '/') . '`?/i';
  if (preg_match($pattern, $mysqlContent) !== 1) {
    $missingTables[] = $table;
  }
}

if (count($missingTables) > 0) {
  fwrite(STDERR, "ERROR: mysql.sql is missing the following tables:\n");
  foreach ($missingTables as $table) {
    fwrite(STDERR, "  - {$table}\n");
  }
  fwrite(STDERR, "\nPlease update mysql.sql to include all migrations.\n");
  fwrite(STDERR, "You can regenerate mysql.sql by:\n");
  fwrite(STDERR, "  1. Running migrations on a fresh database\n");
  fwrite(STDERR, "  2. Exporting the schema with mysqldump\n");
  exit(1);
}

// Additional validation: check migration count vs last migration in mysql.sql
$lastMigration = basename(end($migrationFiles));
if (strpos($mysqlContent, $lastMigration) === false) {
  fwrite(STDOUT, "WARNING: mysql.sql may not include the latest migration: {$lastMigration}\n");
  fwrite(STDOUT, "Please verify mysql.sql includes all schema changes.\n");
}

fwrite(STDOUT, "✓ Migration validation passed\n");
fwrite(STDOUT, "  - Found " . count($migrationFiles) . " migration files\n");
fwrite(STDOUT, "  - All " . count($expectedTables) . " tables present in mysql.sql\n");
exit(0);
