<?php

namespace App\Workflow;

use App\Command\LoadCommand;
use App\Entity\Media;
use Survos\WorkflowBundle\Attribute\Workflow;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Attribute\AsCompletedListener;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Survos\SaisBundle\Service\SaisClientService;
use Survos\SaisBundle\Model\ProcessPayload;
use Symfony\Component\Workflow\WorkflowInterface;

#[Workflow(supports: [Media::class], name: self::WORKFLOW_NAME)]
class MediaWorkflow implements IMediaWorkflow
{
	public const WORKFLOW_NAME = 'MediaWorkflow';

	public function __construct(
        private SaisClientService     $sais,
        private UrlGeneratorInterface $urlGenerator,
        #[Target(self::WORKFLOW_NAME)] private WorkflowInterface $mediaWorkflow,
    )
	{
	}


	public function getMedia(Event $event): Media
	{
		/** @var Media */ return $event->getSubject();
	}



	#[AsTransitionListener(self::WORKFLOW_NAME, self::TRANSITION_DISPATCH)]
	public function onDispatch(TransitionEvent $event): void
	{
		$media = $this->getMedia($event);
        $code = $media->code;

        if ($media->type === 'audio') {
            $resp = $this->sais->dispatchProcess(new ProcessPayload(
                LoadCommand::SAIS_ROOT,
                [$media->getOriginalUrl()],
                mediaCallbackUrl: $this->urlGenerator->generate(
                    'sais_audio_callback',
                    ['code' => $code, '_locale' => 'es'],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ));
            dd($resp);
        } else {
            $resp = $this->sais->dispatchProcess(new ProcessPayload(
                LoadCommand::SAIS_ROOT,
                [$media->getOriginalUrl()],

                mediaCallbackUrl: $this->urlGenerator->generate(
                    'app_media_webhook',
                    ['code' => $code, '_locale' => 'en'],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                thumbCallbackUrl: $this->urlGenerator->generate(
                    'app_thumb_webhook',
                    ['code' => $code, '_locale' => 'en'],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ));
        }

        // Also store immediate response URLs if available
        if ($resized = $resp[0]['resized'] ?? null) {
            $media->resized = $resized;
        }
	}

    #[AsCompletedListener(self::WORKFLOW_NAME, self::TRANSITION_DISPATCH)]
    public function OnDispatchCompleted(CompletedEvent $event): void
    {
        $media = $this->getMedia($event);
        if ($this->mediaWorkflow->can($media, IMediaWorkflow::TRANSITION_RESIZE)) {
            $this->mediaWorkflow->apply($media, IMediaWorkflow::TRANSITION_RESIZE);
        }

    }


	#[AsTransitionListener(self::WORKFLOW_NAME, self::TRANSITION_RESIZE)]
	public function onResize(TransitionEvent $event): void
	{
		$media = $this->getMedia($event);
	}
}
