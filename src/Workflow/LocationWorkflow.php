<?php

namespace App\Workflow;

use App\Entity\Location;
use Survos\GeoapifyBundle\Service\GeoapifyService;
use Survos\WorkflowBundle\Attribute\Workflow;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

#[Workflow(type: 'state_machine', supports: [Location::class], name: self::WORKFLOW_NAME)]
class LocationWorkflow implements ILocationWorkflow
{
    public const WORKFLOW_NAME = 'LocationWorkflow';

    public function __construct(
        private GeoapifyService $geoapifyService,
    )
    {
    }

    private function getLocation(TransitionEvent $event): Location
    {
        /** @var Location */
        return $event->getSubject();
    }

    #[AsTransitionListener(self::WORKFLOW_NAME, self::TRANSITION_GEOCODE)]
    public function onGeocode(TransitionEvent $event): void
    {
        $location = $this->getLocation($event);
        if ($location->getAddress()) {
            $geo = $this->geoapifyService->lookup($location->getAddress())['features'][0]['properties'];
            if (!array_key_exists('lat', $geo)) {
                dd($geo);
            }
            $location->setLat($geo['lat'])
                ->setLng($geo['lon']);
        } else {
            assert(false, "why didnt guard catch this? ");
        }
    }
}
