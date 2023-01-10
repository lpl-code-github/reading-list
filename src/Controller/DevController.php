<?php

namespace App\Controller;

use App\Util\AuthUtil;
use App\Util\Result;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DevController extends AbstractController
{
    /**
     * Returns a token for development
     * env: dev
     * @param AuthUtil $authUtil
     * @param ParameterBagInterface $params
     * @return JsonResponse
     */
    #[Route('/developers', methods: 'GET')]
    public function developers(AuthUtil $authUtil, ParameterBagInterface $params): JsonResponse
    {
        // Create a token
        $encodedToken = $authUtil->encodedToken($params->get('dev')["username"], $params->get('dev')["id"]);
        $result = new Result();
        return $this->json($result->success(
            array(
                "dev_token" => $encodedToken
            )
        ));
    }


    #[Route('/test', name: "test", methods: 'GET')]
    public function test(Request $request): JsonResponse
    {
        $path = str_replace('/', '', $request->getPathInfo());
        $token = $request->query->get("token");
        $apiName = $request->query->get("api_name");

        $result = new Result();
        return $this->json($result->success(
            array(
                "token" => empty($token),
                "route" => $path == $apiName
            )
        ));
    }
}