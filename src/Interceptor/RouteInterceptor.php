<?php

namespace App\Interceptor;

use App\Util\AuthUtil;
use App\Util\Result;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Route Interceptor
 * The array $route defines the route to be intercepted. If it is empty, it will not be intercepted.
 */
class RouteInterceptor implements EventSubscriberInterface
{
    private AuthUtil $authUtil;
    private ParameterBagInterface $params;


    public function __construct(AuthUtil $authUtil,ParameterBagInterface $params)
    {
        $this->authUtil = $authUtil;
        $this->params = $params;
    }

    #[ArrayShape([KernelEvents::REQUEST => "array"])] public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9999],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        // Don't do anything if it's not the master request.
        if (!$event->isMasterRequest()) {
            return;
        }
        $route = array('test','books'); // Array of routes to block

        $result = new Result();
        $response = new JsonResponse();
        $request = $event->getRequest();
        $path = explode('/',$request->getPathInfo())[1];
        $token = $request->query->get("token");
        $apiName = $request->query->get("api_name");

        // Get the request header decode token and recheck
        foreach ($route as $r) {
            if ($path === $r) {
                if (empty($apiName)){ // api_name blank
                    $response->setStatusCode(400);
                    $response->setData($result->paramError("Missing parameter 'api_name'"));
                    $event->setResponse($response);
                    return;
                }else if ($apiName!==$path){ // The correctness of api_name ?
                    $response->setStatusCode(400);
                    $response->setData($result->paramError("Missing parameter error"));
                    $event->setResponse($response);
                    return;
                }
                if (empty($token)) { // token blank ?
                    $response->setStatusCode(400);
                    $response->setData($result->paramError("Missing parameter 'token'"));
                    $event->setResponse($response);
                    return;
                }else{ // The correctness of token ?
                    if ($token== $this->authUtil->encodedToken($this->params->get('dev')["username"], $this->params->get('dev')["id"])){ // dev
                        return;
                    }
                    if ($this->authUtil->verifyToken($token)==false) {
                        $response->setData($result->noPermission("Invalid token."));
                        $event->setResponse($response);
                        return;
                    }
                }
            }
        }
    }
}