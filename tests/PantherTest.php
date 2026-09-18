<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PantherTest extends PantherTestCase
{
    private string $databaseFile;
    private ?string $originalDatabaseUrl;
    private ?string $originalEnvDatabaseUrl;
    private array $serverEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        self::stopWebServer();
        $this->databaseFile = tempnam(sys_get_temp_dir(), 'pgsc-browser-');
        $this->originalDatabaseUrl = $_SERVER['DATABASE_URL'] ?? null;
        $this->originalEnvDatabaseUrl = $_ENV['DATABASE_URL'] ?? null;
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'] = 'sqlite:///'.$this->databaseFile;
        self::bootKernel();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertSame($this->databaseFile, $em->getConnection()->getParams()['path']);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        $user = (new User())->setEmail('admin@example.test')->setRoles(['ROLE_ADMIN'])->setIsVerified(true);
        $user->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, 'test-password'));
        $em->persist($user);
        $em->flush();
        $this->serverEnvironment = [
            'APP_ENV' => 'test',
            'APP_DEBUG' => '1',
            'DATABASE_URL' => $_SERVER['DATABASE_URL'],
            'MAILER_DSN' => 'null://null',
        ];
        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            self::stopWebServer();
            if ($this->originalDatabaseUrl === null) {
                unset($_SERVER['DATABASE_URL']);
            } else {
                $_SERVER['DATABASE_URL'] = $this->originalDatabaseUrl;
            }
            if ($this->originalEnvDatabaseUrl === null) {
                unset($_ENV['DATABASE_URL']);
            } else {
                $_ENV['DATABASE_URL'] = $this->originalEnvDatabaseUrl;
            }
            if (is_file($this->databaseFile)) {
                unlink($this->databaseFile);
            }
        }
    }

    public function testPublicHomepageAndArtists(): void
    {
        $client = self::createPantherClient(['env' => $this->serverEnvironment]);
        $client->request('GET', '/');
        self::assertSelectorTextContains('h1', 'CHIJAL');
        $client->request('GET', '/en/admin/artist');
        self::assertSelectorExists('table.datagrid');
    }

    public function testAdminLogin(): void
    {
        $client = self::createPantherClient(['env' => $this->serverEnvironment]);
        $client->request('GET', '/login');
        $client->submitForm('Sign in', [
            '_username' => 'admin@example.test',
            '_password' => 'test-password',
        ]);
        $client->waitFor('a[href="/logout"]');
        self::assertSelectorExists('a[href="/logout"]');
        $client->request('GET', '/en/admin/artist');
        self::assertSelectorExists('table.datagrid');
    }
}
