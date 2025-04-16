<?php

namespace App\Workflow;

use Survos\WorkflowBundle\Attribute\Place;
use Survos\WorkflowBundle\Attribute\Transition;

interface ISacroWorkflow
{
	public const WORKFLOW_NAME = 'SacroWorkflow';

	#[Place(initial: true)]
	public const PLACE_NEW = 'new';

	#[Place]
	public const PLACE_RESIZED = 'resized';

	#[Transition(from: [self::PLACE_NEW], to: self::PLACE_RESIZED, guard: 'subject.getDriveUrl')]
	public const TRANSITION_RESIZE = 'resize';
}
