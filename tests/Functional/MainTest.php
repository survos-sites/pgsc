<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MainTest extends WebTestCase
{
    public function testHomepage(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertTrue($em->getConnection()->getParams()['memory'] ?? false);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());

        $client->request('GET', '/');
        self::assertResponseRedirects('/en/chijal');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'CHIJAL');
    }
}
