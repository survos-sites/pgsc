<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Entity\Sacro;
use App\Workflow\ISacroWorkflow as WF;
use Survos\WorkflowExtrasBundle\Attribute\Place;
use Survos\WorkflowExtrasBundle\Attribute\Transition;
use Survos\WorkflowExtrasBundle\Attribute\AsWorkflow;

#[AsWorkflow(supports: [Sacro::class], name: WF::WORKFLOW_NAME)]
class ISacroWorkflow
{
	public const WORKFLOW_NAME = 'SacroWorkflow';

	#[Place(initial: true)]
	public const PLACE_NEW = 'new';

	#[Place]
	public const PLACE_RESIZED = 'resized';

	#[Transition(from: [self::PLACE_NEW], to: self::PLACE_RESIZED, guard: 'subject.driveUrl')]
	public const TRANSITION_RESIZE = 'resize';
}
