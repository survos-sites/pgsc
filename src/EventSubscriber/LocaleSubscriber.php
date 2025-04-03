<?php

// src/EventSubscriber/LocaleSubscriber.php
// old, but worth a read: https://rachidtrahim.wordpress.com/2016/04/19/setting-locale-based-on-uri-subdomain-in-symfony2/

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected LoggerInterface $logger,
        #[Autowire('%env(BASE_HOST)%')] private string $baseHost,
        #[Autowire('%kernel.enabled_locales%')] private array $availableLocales,
        #[Autowire('%kernel.default_locale%')] private string $currentLocale, // from browser
    ) {
    }

    public function onKernelRequest(RequestEvent $event)
    {
        return;
        //        $this->currentLocale = $event->getRequest()->getLocale();
        //        $this->logger->warning("Current Locale: " . $this->currentLocale);
        $request = $event->getRequest();
        $this->currentLocale = $request->getLocale();
        //        $request->getHttpHost();
        //        dd($this->currentLocale, $request->getLocale(), $this->baseHost, $request->getHttpHost());

        $subdomain = str_replace('.'.$this->baseHost, '', $request->getHttpHost());
        if ($subdomain === $this->baseHost) {
            $subdomain = null;
        }
        // hack.
        if (in_array($subdomain, ['www', 'museado', 'admin', 'www.museado.com', 'www.museado.org', 'museado.survos.com'])) {
            $subdomain = null;
        }

        // if there's no subdomain, direct to the one in the request, or a backup
        if (!$subdomain) {
            if (in_array($request->getLocale(), $this->availableLocales)) {
                $url = '//'.$request->getLocale().'.'.$this->baseHost;
                $url .= $request->getRequestUri();
                //                dd($url, $request, $request->getUri(), $request->getRequestUri());
                //                dd($this->baseHost);
                //                $url = str_replace('https://', 'https://' . $request->getLocale() . '.', $request->getSchemeAndHttpHost());
                // //                dd($url, $request->getLocale(), $this->availableLocales, $request->getSchemeAndHttpHost());
                // /
                $redirect = new RedirectResponse($url, 302);
                $event->setResponse($redirect);

                return;
            }
            $url = str_replace("//$subdomain.", '//', $url);
            $url = '//'.$this->baseHost.$url;
            $redirect = new RedirectResponse($url, 302);
            $event->setResponse($redirect);

            return;
        }

        if (in_array($subdomain, $this->availableLocales)) {
            $this->currentLocale = $subdomain;
            $request->setLocale($subdomain);
        }

        return;

        //        $locale = $request->query->get('_locale', 'en');
        //        $request->setLocale($locale);
        // was X-LOCALE, Accept-Language is too complicated
        if ($locale = $request->headers->get('X-LOCALE')) {
            // check for available locales
            //            $request->setLocale($locale);
            // see https://medium.com/@titouanbenoit/internationalization-with-api-platform-the-other-way-5ce9c446737f for implementing a LocaleRepo
            if (in_array($locale, $this->availableLocales)) {
                $request->setLocale($locale);
            } else {
                //                $this->logger->error("setting to " . $this->defaultLocale . " Cannot set $locale, only " . join(',', $this->availableLocales));
                $request->setLocale($this->defaultLocale);
            }
        } else {
            $request->setLocale($this->defaultLocale);
        }

        //        $translatable->setTranslatableLocale('fr');

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            // if no explicit locale has been set on this request, use one from the session
            if ($request->hasSession()) {
                $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
            }
        }
        //        dd($request->getLocale(), $request->getDefaultLocale());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            // https://symfony.com/doc/6.3/translation.html#handling-the-user-s-locale
            KernelEvents::REQUEST => ['onKernelRequest', EventPriorities::PRE_SERIALIZE], // must be higher than 16!
            KernelEvents::RESPONSE => ['setContentLanguage'],
            //            KernelEvents::REQUEST => [['onKernelRequest', -20]],
        ];
    }

    public function setContentLanguage(ResponseEvent $event): Response
    {
        $response = $event->getResponse();
        $response->headers->add(['Content-Language' => $this->currentLocale]);

        return $response;
    }
}
