<?php

namespace App\Util\CustomException;

use App\Util\Result;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{

//    private LoggerInterface $logger;
//
//    public function __construct(LoggerInterface $logger)
//    {
//        $this->logger = $logger;
//    }

    public function onKernelException(ExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getThrowable();

        $result = new Result();

        // Customize your response object to display the exception details
        $response = new JsonResponse();


        switch ($exception->getCode()) {
            case 400:
                $response->setData($result->paramError($exception->getMessage()));
                break;
            case 404:
                $response->setData($result->notFound($exception->getMessage()));
                break;
            default:
//                $this->logger->info($exception->getCode());
                $response->setData($result->serverError($exception->getMessage()));
                dd($response);
//                dd($result->serverError($exception->getMessage()));
//                $this->logger->info($result->serverError($exception->getMessage()));
        }


        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // sends the modified response object to the event
        $event->setResponse($response);
    }
}