<?php

namespace App\Workflow;

use App\Entity\Location;
use Survos\GeoapifyBundle\Service\GeoapifyService;
use Survos\WorkflowBundle\Attribute\Workflow;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

#[Workflow(supports: [Location::class], name: self::WORKFLOW_NAME)]
class LocationWorkflow implements ILocationWorkflow
{
    public const WORKFLOW_NAME = 'LocationWorkflow';

    public function __construct(
        private GeoapifyService $geoapifyService,
    ) {
    }


    #[AsGuardListener(self::WORKFLOW_NAME)]
    public function onGuard(GuardEvent $event): void
    {
        /** @var Location $location */
        $location = $event->getSubject();

        switch ($event->getTransition()->getName()) {
        /*
        e.g.
        if ($event->getSubject()->cannotTransition()) {
          $event->setBlocked(true, "reason");
        }
        App\Entity\Location
        */
            case self::TRANSITION_GEOCODE:
                break;
            case self::TRANSITION_APPROVE:
                break;
        }
    }


    #[AsTransitionListener(self::WORKFLOW_NAME)]
    public function onTransition(TransitionEvent $event): void
    {
        /** @var Location location */
        $location = $event->getSubject();

        switch ($event->getTransition()->getName()) {
        /*
        e.g.
        if ($event->getSubject()->cannotTransition()) {
          $event->setBlocked(true, "reason");
        }
        App\Entity\Location
        */
            case self::TRANSITION_GEOCODE:
                if ($location->getAddress()) {
                    $geo = $this->geoapifyService->lookup($location->getAddress())['features'][0]['properties'];
                    if (!array_key_exists('lat', $geo)) {
                        dd($geo);
                    }
                    $location->setLat($geo['lat'])
                        ->setLng($geo['lon']);
                }
                break;
            case self::TRANSITION_APPROVE:
                break;
        }
    }
}
