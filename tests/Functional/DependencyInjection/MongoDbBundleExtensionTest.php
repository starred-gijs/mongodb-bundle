<?php

namespace Facile\MongoDbBundle\Tests\functional\DependencyInjection;

use Facile\MongoDbBundle\DependencyInjection\MongoDbBundleExtension;
use Facile\MongoDbBundle\Services\Loggers\DataCollectorLoggerInterface;
use Facile\MongoDbBundle\Services\Loggers\MongoLogger;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use MongoDB\Database;
use Facile\MongoDbBundle\Capsule\Database as LoggerDatabase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class MongoDbBundleExtensionTest extends AbstractExtensionTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->setParameter('kernel.environment', 'dev');
        $this->container->setDefinition('debug.stopwatch', new Definition(Stopwatch::class));
    }

    public function test_load()
    {
        $this->load(
            [
                'clients' => [
                    'test_client' => [
                        'host' => 'localhost',
                        'port' => 8080,
                        'username' => 'foo',
                        'password' => 'bar',
                    ],
                ],
                'connections' => [
                    'test_db' => [
                        'client_name' => 'test_client',
                        'database_name' => 'testdb',
                    ],
                ],
            ]
        );
        $this->compile();
        // Alias connections
        $this->assertContainerBuilderHasService('mongo.connection', Database::class);
        $defaultConnection = $this->container->get('mongo.connection');
        $this->assertInstanceOf(Database::class, $defaultConnection);
        $this->assertInstanceOf(LoggerDatabase::class, $defaultConnection);
        $this->assertSame('testdb', $defaultConnection->getDatabaseName());

        // 'test_db' connection
        $this->assertContainerBuilderHasService('mongo.connection.test_db', Database::class);
        $defaultConnection = $this->container->get('mongo.connection.test_db');

        $this->assertInstanceOf(Database::class, $defaultConnection);
        $this->assertInstanceOf(LoggerDatabase::class, $defaultConnection);
        $this->assertSame('testdb', $defaultConnection->getDatabaseName());

        $this->assertContainerBuilderHasService('facile_mongo_db.logger', MongoLogger::class);
        $logger = $this->container->get('facile_mongo_db.logger');
        $this->assertInstanceOf(MongoLogger::class, $logger);
    }

    public function test_load_env_prod()
    {
        $this->setParameter('kernel.environment', 'prod');
        $this->load(
            [
                'clients' => [
                    'test_client' => [
                        'host' => 'localhost',
                        'port' => 8080,
                        'username' => 'foo',
                        'password' => 'bar',
                    ],
                ],
                'connections' => [
                    'test_db' => [
                        'client_name' => 'test_client',
                        'database_name' => 'testdb',
                    ],
                ],
            ]
        );
        $this->compile();

        // 'test_db' connection
        $this->assertContainerBuilderHasService('mongo.connection.test_db', Database::class);
        $defaultConnection = $this->container->get('mongo.connection.test_db');

        $this->assertInstanceOf(Database::class, $defaultConnection);
        $this->assertNotInstanceOf(LoggerDatabase::class, $defaultConnection);
        $this->assertSame('testdb', $defaultConnection->getDatabaseName());
    }

    public function test_load_multiple()
    {
        $this->load(
            [
                'clients' => [
                    'test_client' => [
                        'host' => 'localhost',
                        'port' => 8080,
                        'username' => 'foo',
                        'password' => 'bar',
                    ],
                    'other_client' => [
                        'host' => 'localhost.dev',
                        'port' => 8081,
                        'username' => 'mee',
                        'password' => 'zod',
                    ],
                ],
                'connections' => [
                    'test_db' => [
                        'client_name' => 'test_client',
                        'database_name' => 'testdb',
                    ],
                    'other_db' => [
                        'client_name' => 'other_client',
                        'database_name' => 'otherdb',
                    ],
                    'test_db_2' => [
                        'client_name' => 'test_client',
                        'database_name' => 'testdb_2',
                    ],
                ],
            ]
        );
        $this->compile();

        // Alias connection
        $this->assertContainerBuilderHasService('mongo.connection', Database::class);
        $defaultConnection = $this->container->get('mongo.connection');
        $this->assertInstanceOf(Database::class, $defaultConnection);
        $this->assertSame('testdb', $defaultConnection->getDatabaseName());

        // 'test_db' connection
        $this->assertContainerBuilderHasService('mongo.connection.test_db', Database::class);
        $defaultConnection = $this->container->get('mongo.connection.test_db');
        $this->assertInstanceOf(Database::class, $defaultConnection);
        $this->assertSame('testdb', $defaultConnection->getDatabaseName());

        // 'other_db' connection
        $this->assertContainerBuilderHasService('mongo.connection.other_db', Database::class);
        $testConnection = $this->container->get('mongo.connection.other_db');
        $this->assertInstanceOf(Database::class, $testConnection);
        $this->assertSame('otherdb', $testConnection->getDatabaseName());

        // 'test_db_2' connection
        $this->assertContainerBuilderHasService('mongo.connection.test_db_2', Database::class);
        $testConnection = $this->container->get('mongo.connection.test_db_2');
        $this->assertInstanceOf(Database::class, $testConnection);
        $this->assertSame('testdb_2', $testConnection->getDatabaseName());
    }

    /**
     * Return an array of container extensions you need to be registered for each test (usually just the container
     * extension you are testing.
     *
     * @return ExtensionInterface[]
     */
    protected function getContainerExtensions()
    {
        return [
            new MongoDbBundleExtension(),
        ];
    }
}
