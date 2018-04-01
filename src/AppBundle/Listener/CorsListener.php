<?php

namespace AppBundle\Listener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class CorsListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $responseHeaders = $event->getResponse()->headers;
        CorsListener::setCORS($responseHeaders);
    }

    /**
     * @param $responseHeaders \Symfony\Component\HttpFoundation\ResponseHeaderBag
     */
    private static function setCORS($responseHeaders)
    {
        $responseHeaders->set('Access-Control-Allow-Credentials', 'true');
        $responseHeaders->set('Access-Control-Allow-Origin', '*');
        $responseHeaders->set('Vary', 'Origin');
        $responseHeaders->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
        $responseHeaders->set('Access-Control-Allow-Headers', 'Accept, Authorization, Cache-Control, Content-Type, DNT, If-Modified-Since, Keep-Alive, Origin, User-Agent, X-Requested-With, APIKEY, COMPANYUSERID, COMPANYIDi, enctype');
        $responseHeaders->set('Access-Control-Max-Age', 3600);
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        /* @var $request \Symfony\Component\HttpFoundation\Request */
        $request = $event->getRequest();

        if ($request->getMethod() == 'OPTIONS' || $request->getMethod() == 'OPTION') {
            $response = new Response('We have no response for a JSON request', 204);
            $responseHeaders = $response->headers;
            CorsListener::setCORS($responseHeaders);
            $responseHeaders->set('Content-Type', 'text/plain charset=UTF-8');
            $responseHeaders->set('Content-Length', '0');
            $event->setResponse($response);
        }
    }
}
