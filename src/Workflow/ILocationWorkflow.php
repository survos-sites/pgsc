<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Entity\Location;
use Survos\WorkflowExtrasBundle\Attribute\Place;
use Survos\WorkflowExtrasBundle\Attribute\Transition;
use Survos\WorkflowExtrasBundle\Attribute\AsWorkflow;

#[AsWorkflow(supports: [Location::class], name: self::WORKFLOW_NAME)]
final class ILocationWorkflow
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
        guard: 'subject.address',
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
