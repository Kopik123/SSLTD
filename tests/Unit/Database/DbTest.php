<?php
declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Database\Db;
use App\Support\Config;
use PHPUnit\Framework\TestCase;
use PDO;
use RuntimeException;

final class DbTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new Config([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
        ]);
    }

    public function testConnectWithSqlite(): void
    {
        $db = Db::connect($this->config);
        $this->assertInstanceOf(Db::class, $db);
        $this->assertInstanceOf(PDO::class, $db->pdo());
    }

    public function testPdoReturnsCorrectInstance(): void
    {
        $db = Db::connect($this->config);
        $pdo = $db->pdo();
        
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertEquals(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    public function testFetchOneReturnsNullWhenNoResults(): void
    {
        $db = Db::connect($this->config);
        $db->pdo()->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        
        $result = $db->fetchOne('SELECT * FROM test WHERE id = :id', ['id' => 999]);
        $this->assertNull($result);
    }

    public function testFetchOneReturnsRowWhenFound(): void
    {
        $db = Db::connect($this->config);
        $db->pdo()->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $db->pdo()->exec("INSERT INTO test (id, name) VALUES (1, 'Test')");
        
        $result = $db->fetchOne('SELECT * FROM test WHERE id = :id', ['id' => 1]);
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Test', $result['name']);
    }

    public function testFetchAllReturnsEmptyArrayWhenNoResults(): void
    {
        $db = Db::connect($this->config);
        $db->pdo()->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        
        $results = $db->fetchAll('SELECT * FROM test WHERE id > :id', ['id' => 0]);
        $this->assertIsArray($results);
        $this->assertCount(0, $results);
    }

    public function testFetchAllReturnsMultipleRows(): void
    {
        $db = Db::connect($this->config);
        $db->pdo()->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $db->pdo()->exec("INSERT INTO test (id, name) VALUES (1, 'One'), (2, 'Two'), (3, 'Three')");
        
        $results = $db->fetchAll('SELECT * FROM test ORDER BY id');
        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        $this->assertEquals('One', $results[0]['name']);
        $this->assertEquals('Two', $results[1]['name']);
        $this->assertEquals('Three', $results[2]['name']);
    }

    public function testInsertReturnsLastInsertId(): void
    {
        $db = Db::connect($this->config);
        $db->pdo()->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        
        $id1 = $db->insert('INSERT INTO test (name) VALUES (:name)', ['name' => 'First']);
        $id2 = $db->insert('INSERT INTO test (name) VALUES (:name)', ['name' => 'Second']);
        
        $this->assertEquals('1', $id1);
        $this->assertEquals('2', $id2);
    }

    public function testExecReturnsAffectedRows(): void
    {
        $db = Db::connect($this->config);
        $db->pdo()->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $db->pdo()->exec("INSERT INTO test (id, name) VALUES (1, 'One'), (2, 'Two'), (3, 'Three')");
        
        $affected = $db->exec('UPDATE test SET name = :name WHERE id > :id', ['name' => 'Updated', 'id' => 1]);
        $this->assertEquals(2, $affected);
    }

    public function testSqliteForeignKeysEnabled(): void
    {
        $db = Db::connect($this->config);
        $result = $db->fetchOne('PRAGMA foreign_keys');
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['foreign_keys'] ?? $result[0] ?? 0);
    }

    public function testPreparedStatementsPreventSqlInjection(): void
    {
        $db = Db::connect($this->config);
        $db->pdo()->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $db->pdo()->exec("INSERT INTO test (id, name) VALUES (1, 'Test')");
        
        // Attempt SQL injection via parameter
        $maliciousInput = "1 OR 1=1";
        $result = $db->fetchOne('SELECT * FROM test WHERE id = :id', ['id' => $maliciousInput]);
        
        // Should return null because the string doesn't match any integer ID
        $this->assertNull($result);
    }
}
