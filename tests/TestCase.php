<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PDO;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $this->isolateTestingDatabase($app);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Évite le dump schema/*.sql (besoin du binaire `mysql`, absent de laravel_app).
     *
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing()
    {
        $seeder = property_exists($this, 'seeder') ? $this->seeder : false;

        return array_merge(
            [
                '--drop-views' => property_exists($this, 'dropViews') ? $this->dropViews : false,
                '--drop-types' => property_exists($this, 'dropTypes') ? $this->dropTypes : false,
                '--schema-path' => database_path('schema/__skip_cli_dump.sql'),
            ],
            $seeder ? ['--seeder' => $seeder] : ['--seed' => property_exists($this, 'seed') ? $this->seed : false],
        );
    }

    protected function isolateTestingDatabase(Application $app): void
    {
        $driver = $app['config']->get('database.default');

        if ($driver === 'sqlite') {
            $app['config']->set('database.connections.sqlite.database', ':memory:');
            $app['db']->purge('sqlite');

            return;
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $app['config']->set("database.connections.{$driver}.database", 'testing');
        $this->ensureMysqlTestingDatabase($app, $driver);
        $app['db']->purge($driver);
    }

    protected function ensureMysqlTestingDatabase(Application $app, string $driver): void
    {
        $config = $app['config']->get("database.connections.{$driver}");

        $dsn = sprintf(
            'mysql:host=%s;port=%s',
            $config['host'],
            $config['port'] ?? 3306,
        );

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `testing` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                'Impossible de préparer la base de tests `testing` (hôte '.$config['host'].'). '.$e->getMessage(),
                (int) $e->getCode(),
                $e,
            );
        }
    }
}
