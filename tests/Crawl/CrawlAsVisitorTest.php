<?php

namespace App\Tests\Crawl;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Survos\CrawlerBundle\Tests\BaseVisitLinksTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CrawlAsVisitorTest extends BaseVisitLinksTest
{
	#[TestDox('/$method $url ($route)')]
	#[TestWith(['', '/es/admin/artist', 200])]
	#[TestWith(['', '/es/admin/artist/new', 200])]
	#[TestWith(['', '/es/admin/artist/render-filters', 200])]
	#[TestWith(['', '/es/admin/location', 200])]
	#[TestWith(['', '/es/admin/location/new', 200])]
	#[TestWith(['', '/es/admin/location/render-filters', 200])]
	#[TestWith(['', '/es/admin/media', 200])]
	#[TestWith(['', '/es/admin/media/new', 200])]
	#[TestWith(['', '/es/admin/media/render-filters', 200])]
	#[TestWith(['', '/es/admin/obra', 200])]
	#[TestWith(['', '/es/admin/obra/new', 200])]
	#[TestWith(['', '/es/admin/obra/render-filters', 200])]
	#[TestWith(['', '/es/admin/sacro', 200])]
	#[TestWith(['', '/es/admin/sacro/new', 200])]
	#[TestWith(['', '/es/admin/sacro/render-filters', 200])]
	#[TestWith(['', '/auth/profile', 200])]
	#[TestWith(['', '/auth/providers', 200])]
	#[TestWith(['', '/admin/commands/', 200])]
	#[TestWith(['', '/crawler/crawlerdata', 200])]
	#[TestWith(['', '/workflow/', 200])]
	#[TestWith(['', '/workflow/entities', 200])]
	#[TestWith(['', '/register', 200])]
	#[TestWith(['', '/verify/email', 200])]
	#[TestWith(['', '/login', 200])]
	#[TestWith(['', '/', 200])]
	#[TestWith(['', '/es/admin', 200])]
	#[TestWith(['', '/es/sync', 200])]
	#[TestWith(['', '/es/landing', 200])]
	#[TestWith(['', '/es/api/docs', 200])]
	#[TestWith(['', '/es/jsonrpc/test', 200])]
	#[TestWith(['', '/es/cmas', 200])]
	#[TestWith(['', '/es/cmas/import', 200])]
	#[TestWith(['', '/es/cmas-images', 200])]
	#[TestWith(['', '/auth/provider/amazon', 200])]
	#[TestWith(['', '/auth/provider/auth0', 200])]
	#[TestWith(['', '/auth/provider/azure', 200])]
	#[TestWith(['', '/auth/provider/bitbucket', 200])]
	#[TestWith(['', '/admin/commands/run/app:load', 200])]
	#[TestWith(['', '/admin/commands/run/survos:workflow:viz', 200])]
	#[TestWith(['', '/admin/commands/run/survos:workflow:dump', 200])]
	#[TestWith(['', '/admin/commands/run/survos:workflow:generate', 200])]
	#[TestWith(['', '/workflow/workflow/MediaWorkflow', 200])]
	#[TestWith(['', '/workflow/workflow/LocationWorkflow', 200])]
	#[TestWith(['', '/workflow/workflow/SacroWorkflow', 200])]
	#[TestWith(['', '/workflow/workflow/MediaWorkflow?states=%22new%22', 200])]
	public function testRoute(string $username, string $url, string|int|null $expected): void
	{
		parent::loginAsUserAndVisit($username, $url, (int)$expected);
	}
}
