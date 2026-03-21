<?php

namespace App\Workflow;

use App\Entity\Sacro;
use Survos\StateBundle\Attribute\Workflow;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use App\Workflow\ISacroWorkflow as WF;

class SacroWorkflow
{
	public const WORKFLOW_NAME = 'SacroWorkflow';

	public function __construct()
	{
	}

	public function getSacro(TransitionEvent|GuardEvent $event): Sacro
	{
		/** @var Sacro */ return $event->getSubject();
	}

	#[AsTransitionListener(WF::WORKFLOW_NAME, WF::TRANSITION_RESIZE)]
	public function onTransition(TransitionEvent $event): void
	{
		// @todo re-implement resize via media-bundle when Sacro workflow is migrated
	}
}
