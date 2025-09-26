<?php

namespace App\Workflow;

use App\Entity\Sacro;
use Survos\SaisBundle\Model\ProcessPayload;
use Survos\SaisBundle\Service\SaisClientService;
use Survos\WorkflowBundle\Attribute\Workflow;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

#[Workflow(supports: [Sacro::class], name: self::WORKFLOW_NAME)]
class SacroWorkflow implements ISacroWorkflow
{
	public const WORKFLOW_NAME = 'SacroWorkflow';

	public function __construct(
        private SaisClientService $saisClientService
    )
	{
	}
	public function getSacro(TransitionEvent|GuardEvent $event): Sacro
	{
		/** @var Sacro */ return $event->getSubject();
	}

	#[AsTransitionListener(self::WORKFLOW_NAME, self::TRANSITION_RESIZE)]
	public function onTransition(TransitionEvent $event): void
	{
		$sacro = $this->getSacro($event);
        $result = $this->saisClientService->dispatchProcess(
            new ProcessPayload('sacro', [
                $sacro->driveUrl
            ])
        );
	}
}
