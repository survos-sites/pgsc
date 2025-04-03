<?php

namespace App\Workflow;

use Survos\WorkflowBundle\Attribute\Place;
use Survos\WorkflowBundle\Attribute\Transition;

interface ILocationWorkflow
{
	public const WORKFLOW_NAME = 'LocationWorkflow';

	#[Place(initial: true)]
	public const PLACE_NEW = 'new';

	#[Place]
	public const PLACE_GEOCODED = 'geocoded';

	#[Place]
	public const PLACE_APPROVED = 'approved';

	#[Transition(from: [self::PLACE_NEW], to: self::PLACE_GEOCODED)]
	public const TRANSITION_GEOCODE = 'geocode';

	#[Transition(from: [self::PLACE_GEOCODED], to: self::PLACE_APPROVED)]
	public const TRANSITION_APPROVE = 'approve';
}
