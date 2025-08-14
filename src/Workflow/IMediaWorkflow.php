<?php

namespace App\Workflow;

use Survos\WorkflowBundle\Attribute\Place;
use Survos\WorkflowBundle\Attribute\Transition;

interface IMediaWorkflow
{
	public const WORKFLOW_NAME = 'MediaWorkflow';

	#[Place(initial: true)]
	public const PLACE_NEW = 'new';

	#[Place]
	public const PLACE_DISPATCHED = 'dispatched';

	#[Place]
	public const PLACE_RESIZED = 'resized';

	#[Transition(from: [self::PLACE_NEW], to: self::PLACE_DISPATCHED)]
	public const TRANSITION_DISPATCH = 'dispatch';

	#[Transition(from: [self::PLACE_DISPATCHED], to: self::PLACE_RESIZED,
    guard: "subject.resized != []"
    )]
	public const TRANSITION_RESIZE = 'resize';
}
