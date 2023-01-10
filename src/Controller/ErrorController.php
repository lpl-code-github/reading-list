<?php

namespace App\Controller;

use App\Util\Result;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class ErrorController extends AbstractController
{
    /**
     * Custom error controller
     * @param Throwable $exception
     * @return Response
     */
    #[Route('/error', name: 'app_error')]
    public function show(Throwable $exception): Response
    {
        $result = new Result();
        $response = new Response();
        $response->headers->set('Content-Type',"application/json; charset=UTF-8");
        return match ($exception->getCode()) {
            400 => $response->setContent(json_encode($result->paramError($exception->getMessage())))->setStatusCode(Response::HTTP_BAD_REQUEST),
            404 => $response->setContent(json_encode($result->notFound($exception->getMessage())))->setStatusCode(Response::HTTP_NOT_FOUND),
            default => $response->setContent(json_encode($result->serverError($exception->getMessage())))->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR),
        };
    }
}
