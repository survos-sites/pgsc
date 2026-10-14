<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Location;
use App\Entity\Sacro;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Registry;

final class NativeWorkflowTest extends KernelTestCase
{
    public function testBothWorkflowsDiscoverTheirSubjectsAndGuards(): void
    {
        self::bootKernel();
        self::assertArrayNotHasKey('SurvosStateBundle', self::$kernel->getBundles());
        self::assertArrayHasKey('SurvosMenuBundle', self::$kernel->getBundles());
        $registry = self::getContainer()->get(Registry::class);
        $location = new Location('test-location');
        $locationWorkflow = $registry->get($location, 'LocationWorkflow');
        self::assertSame(['new' => 1], $locationWorkflow->getMarking($location)->getPlaces());
        self::assertFalse($locationWorkflow->can($location, 'geocode'));
        $location->address = 'San Cristobal';
        self::assertTrue($locationWorkflow->can($location, 'geocode'));
        $sacro = new Sacro('test-sacro');
        $sacroWorkflow = $registry->get($sacro, 'SacroWorkflow');
        self::assertSame(['new' => 1], $sacroWorkflow->getMarking($sacro)->getPlaces());
        self::assertFalse($sacroWorkflow->can($sacro, 'resize'));
    }
}
