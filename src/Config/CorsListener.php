<?php

namespace App\Config;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/*
 * Cors Listener
 */
final class CorsListener implements EventSubscriberInterface
{
    #[ArrayShape([KernelEvents::REQUEST => "array", KernelEvents::RESPONSE => "array", KernelEvents::EXCEPTION => "array"])] public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9999],
            KernelEvents::RESPONSE => ['onKernelResponse', 9999],
            KernelEvents::EXCEPTION => ['onKernelException', 9999],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->extracted($event);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Don't do anything if it's not the master request.
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $method = $request->getRealMethod();

        if (Request::METHOD_OPTIONS === $method) {
            $response = new Response();
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Don't do anything if it's not the master request.
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->extracted($event);
    }

    /**
     * @param ResponseEvent $event
     * @return void
     */
    public function extracted(KernelEvent $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();
        $allowedOrigins = array("http://localhost:8080", "http://localhost:8081");
        $origin = $request->headers->get("Origin");

        if ($response) {
            if (in_array($origin, $allowedOrigins)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH');
                $response->headers->set("Access-Control-Max-Age", "3600");
                $response->headers->set("Access-Control-Allow-Credentials", "true");
                $response->headers->set("Access-Control-Allow-Headers", "Authorization,Origin,X-Requested-With,Content-Type,Accept,content-Type,origin,x-requested-with,content-type,accept,authorization,token,id,X-Custom-Header,X-Cookie,Connection,User-Agent,Cookie,*");
                $response->headers->set("Access-Control-Request-Headers", "Authorization,Origin, X-Requested-With,content-Type,Accept");
                $response->headers->set("Access-Control-Expose-Headers", "*");
            }
        }
    }
}