<?php

namespace AppBundle\Cors;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                ['onKernelResponse', -255], // Run last.
            ],
            KernelEvents::EXCEPTION => [
                ['onKernelException', -255], // Run last.
            ],
        ];
    }

    /**
     * Adds appropriate CORS headers to the response, when applicable.
     *
     * @param   FilterResponseEvent     $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (false === $event->isMasterRequest()) {
            return;
        }

        $request  = $event->getRequest();
        $response = $event->getResponse();

        $origin = $request->headers->get('Origin');
        if (empty($origin)) {
            // Same domain request, do not process further.
            return;
        }

        if ('OPTIONS' === $request->getMethod()) {
            // Allow the pre-flight request to go through without checking application settings, as an app will not be defined.
            $this->handlePreFlightResponse($response, $origin);
            return;
        }
        $this->handleResponse($response, $origin);
    }

    /**
     * Adds appropriate CORS headers to an exception response, when applicable.
     *
     * @param   FilterResponseEvent     $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();
        $origin   = $request->headers->get('Origin');
        if (false === $event->isMasterRequest() || empty($origin)) {
            return;
        }
        if (null === $response) {
            return;
        }
        $this->handleResponse($response, $origin);
    }

    /**
     * Appends the appropriate headers and properties to the response of a CORS preflight request.
     *
     * @param   Response    $response
     * @param   string      $origin
     */
    private function handlePreFlightResponse(Response $response, $origin)
    {
        $maxAge = 86400;
        $response->setStatusCode(200);
        $response->setContent(null);
        $response->setPublic();
        $response->setSharedMaxAge($maxAge);

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PATCH, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'content-type');
        $response->headers->set('Access-Control-Max-Age', $maxAge);
    }

    /**
     * Appends the appropriate headers to response requring CORS.
     *
     * @param   Response    $response
     * @param   string      $origin
     */
    private function handleResponse(Response $response, $origin)
    {
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        // Append Origin to the Vary
        $response->setVary('Origin', false);
    }
}
