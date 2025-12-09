<?php

namespace App\Workflow;

use App\Entity\Location;
use Survos\StateBundle\Attribute\Place;
use Survos\StateBundle\Attribute\Transition;
use Survos\StateBundle\Attribute\Workflow;

#[Workflow(type: 'state_machine', supports: [Location::class], name: self::WORKFLOW_NAME)]
interface ILocationWorkflow
{
    public const WORKFLOW_NAME = 'LocationWorkflow';

    #[Place(initial: true, metadata: ['description' => "starting point, after persisted"])]
    public const string PLACE_NEW = 'new';

    #[Place]
    public const string PLACE_GEOCODED = 'geocoded';

    #[Place(metadata: ['description' => "this place is now visible on the map and app"])]
    public const PLACE_APPROVED = 'approved';

    #[Transition(
        from: [self::PLACE_NEW],
        to: self::PLACE_GEOCODED,
        guard: 'subject.getAddress()',
        metadata: [
//            'color' => 'green',
//            'arrow_color' => 'blue',
            'description' => "geocode the address if it exists", 'engine' => 'geoapify', 'bg_color' => 'pink']
    )]
    public const TRANSITION_GEOCODE = 'geocode';

    #[Transition(
        from: [self::PLACE_GEOCODED],
        to: self::PLACE_APPROVED,
        guard: 'is_granted("ROLE_ADMIN")',
    )]
    public const TRANSITION_APPROVE = 'approve';
}
