<?php
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testDbConfigFileExists()
    {
        $this->assertFileExists(__DIR__ . '/../config/db.php');
    }
    
    public function testIndexFileExists()
    {
        $this->assertFileExists(__DIR__ . '/../index.php');
    }
    
    public function testDatabaseSqlExists()
    {
        $this->assertFileExists(__DIR__ . '/../database.sql');
    }
    
    public function testDockerfileExists()
    {
        $this->assertFileExists(__DIR__ . '/../Dockerfile');
    }
    
    public function testDockerComposeExists()
    {
        $this->assertFileExists(__DIR__ . '/../docker-compose.yml');
    }
}
