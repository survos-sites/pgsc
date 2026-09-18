<?php

namespace App\Tests\Crawl;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CrawlAsVisitorTest extends WebTestCase
{
	#[TestDox('/$method $url ($route)')]
	#[TestWith(['', '/es/admin/artist', 200])]
	#[TestWith(['', '/es/admin/artist/new', 200])]
	#[TestWith(['', '/es/admin/artist/render-filters', 200])]
	#[TestWith(['', '/es/admin/location', 200])]
	#[TestWith(['', '/es/admin/location/new', 200])]
	#[TestWith(['', '/es/admin/location/render-filters', 200])]
	#[TestWith(['', '/es/admin/media', 200])]
	#[TestWith(['', '/es/admin/media/new', 403])]
	#[TestWith(['', '/es/admin/media/render-filters', 200])]
	#[TestWith(['', '/es/admin/obra', 200])]
	#[TestWith(['', '/es/admin/obra/new', 200])]
	#[TestWith(['', '/es/admin/obra/render-filters', 200])]
	#[TestWith(['', '/es/admin/sacro', 200])]
	#[TestWith(['', '/es/admin/sacro/new', 403])]
	#[TestWith(['', '/es/admin/sacro/render-filters', 200])]
	#[TestWith(['', '/auth/profile', 302])]
	#[TestWith(['', '/auth/providers', 200])]
	#[TestWith(['', '/admin/commands/', 404])]
	#[TestWith(['', '/crawler/crawlerdata', 200])]
	#[TestWith(['', '/workflow/', 404])]
	#[TestWith(['', '/workflow/entities', 404])]
	#[TestWith(['', '/register', 200])]
	#[TestWith(['', '/verify/email', 302])]
	#[TestWith(['', '/login', 200])]
	#[TestWith(['', '/', 302])]
	#[TestWith(['', '/es/admin', 200])]
	#[TestWith(['', '/es/sync', 200])]
	#[TestWith(['', '/es/landing', 200])]
	#[TestWith(['', '/es/api/docs', 404])]
	#[TestWith(['', '/es/jsonrpc/test', 404])]
	#[TestWith(['', '/es/cmas', 200])]
	#[TestWith(['', '/es/cmas/import', 200])]
	#[TestWith(['', '/es/cmas-images', 302])]
	#[TestWith(['', '/auth/provider/amazon', 200])]
	#[TestWith(['', '/auth/provider/auth0', 200])]
	#[TestWith(['', '/auth/provider/azure', 200])]
	#[TestWith(['', '/auth/provider/bitbucket', 200])]
	#[TestWith(['', '/admin/commands/run/app:load', 404])]
	#[TestWith(['', '/admin/commands/run/survos:workflow:viz', 404])]
	#[TestWith(['', '/admin/commands/run/survos:workflow:dump', 404])]
	#[TestWith(['', '/admin/commands/run/survos:workflow:generate', 404])]
	#[TestWith(['', '/workflow/workflow/MediaWorkflow', 404])]
	#[TestWith(['', '/workflow/workflow/LocationWorkflow', 404])]
	#[TestWith(['', '/workflow/workflow/SacroWorkflow', 404])]
	#[TestWith(['', '/workflow/workflow/MediaWorkflow?states=%22new%22', 404])]
	public function testRoute(string $username, string $url, string|int|null $expected): void
	{
		$client = self::createClient();
        $client->disableReboot();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertTrue($em->getConnection()->getParams()['memory'] ?? false);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        if ($url === '/es/sync') {
            $sync = $this->createMock(\App\Service\SyncService::class);
            $sync->expects(self::once())->method('sync')->with(false)->willReturn([
                'artists' => 0, 'locations' => 0, 'obras' => 0, 'skipped' => [], 'warnings' => [],
            ]);
            self::getContainer()->set(\App\Service\SyncService::class, $sync);
        }
        $client->request('GET', $url);
        self::assertResponseStatusCodeSame((int) $expected);
	}
}
