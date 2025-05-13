<?php

namespace App\Tests\Crawl;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Survos\CrawlerBundle\Tests\BaseVisitLinksTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CrawlAsAdminTest extends BaseVisitLinksTest
{
	#[TestDox('/$method $url ($route)')]
	#[TestWith(['admin@test.com', '/es/admin/artist', 200])]
	#[TestWith(['admin@test.com', '/es/admin/artist/new', 200])]
	#[TestWith(['admin@test.com', '/es/admin/artist/render-filters', 200])]
	#[TestWith(['admin@test.com', '/es/admin/location', 200])]
	#[TestWith(['admin@test.com', '/es/admin/location/new', 200])]
	#[TestWith(['admin@test.com', '/es/admin/location/render-filters', 200])]
	#[TestWith(['admin@test.com', '/es/admin/obra', 200])]
	#[TestWith(['admin@test.com', '/es/admin/obra/new', 200])]
	#[TestWith(['admin@test.com', '/es/admin/obra/render-filters', 200])]
	#[TestWith(['admin@test.com', '/es/admin/sacro', 200])]
	#[TestWith(['admin@test.com', '/es/admin/sacro/new', 200])]
	#[TestWith(['admin@test.com', '/es/admin/sacro/render-filters', 200])]
	#[TestWith(['admin@test.com', '/auth/profile', 200])]
	#[TestWith(['admin@test.com', '/auth/providers', 200])]
	#[TestWith(['admin@test.com', '/admin/commands/', 200])]
	#[TestWith(['admin@test.com', '/crawler/crawlerdata', 200])]
	#[TestWith(['admin@test.com', '/workflow/', 200])]
	#[TestWith(['admin@test.com', '/register', 200])]
	#[TestWith(['admin@test.com', '/verify/email', 200])]
	#[TestWith(['admin@test.com', '/login', 200])]
	#[TestWith(['admin@test.com', '/', 200])]
	public function testRoute(string $username, string $url, string|int|null $expected): void
	{
		parent::loginAsUserAndVisit($username, $url, (int)$expected);
	}
}
