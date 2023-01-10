<?php

namespace App\Controller;


use App\Service\PlanService;
use App\Util\AuthUtil;
use App\Util\CustomException\NotFoundException;
use App\Util\CustomException\ParamErrorException;
use App\Util\Result;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PlanController extends AbstractController
{
    private AuthUtil $authUtil;
    public function __construct(AuthUtil $authUtil)
    {
        $this->authUtil = $authUtil;
    }

    /**
     * Setting reading status
     * @param Request $request
     * @param PlanService $planService
     * @return JsonResponse
     * @throws ParamErrorException
     * @throws NotFoundException
     */
    #[Route('/plans', name: 'save_plan', methods: 'POST')]
    public function savePlan(Request $request, PlanService $planService): JsonResponse
    {
        // Get request body parameters
        try {
            $resource = $request->toArray();
        } catch (Exception $e) {
            throw new ParamErrorException('Missing request body in json format or parameter format is not JSON');
        }

        // Validity verification
        $this->checkPlanParam($resource,false);

        $userId = $this->authUtil->getUserId($request);
        $plan = $planService->savePlan($userId,$resource);

        $result = new Result();
        return $this->json($result->success(
            array(
                "plan"=>array(
                    "user_id"=>$plan->getUserId(),
                    "book_id"=>$plan->getBookId(),
                    "read_page"=>$plan->getReadPage()
                )
            )
        ));
    }


    /**
     * Verification parameters
     * @param array $resource
     * @param bool $allowBlank
     * @return void
     * @throws ParamErrorException
     */
    private function checkPlanParam(array $resource, bool $allowBlank)
    {
        $paramArray = array("book_id", "read_page"); // 需要校验的参数
        foreach ($paramArray as $item){
            if (array_key_exists($item, $resource)) {
                // If there are parameters, call the verification method corresponding to the parameters
                $param = $resource[$item];
                match ($item) {
                    "book_id" => $this->checkBookId($param),
                    "read_page" => $this->checkReadPage($param),
                };
            }else if (!$allowBlank){
                // No parameter exists and $allowBlank is false
                throw new ParamErrorException('Missing parameter');
            }
        }
    }

    /**
     * Verify Book Id
     * @param int $bookId
     * @return void
     * @throws ParamErrorException
     */
    private function checkBookId(int $bookId ){
        if (!preg_match("/\d+/", $bookId)==1){
            throw new ParamErrorException('The book_id is error.');
        }
    }

    /**
     * Verify book read page
     * @param int $readPage
     * @return void
     * @throws ParamErrorException
     */
    private function checkReadPage(int $readPage){
        if (!preg_match("/^[1-9]\d{0,4}$/", $readPage)==1){
            throw new ParamErrorException('The number of read_page must be between 0 and 99999.');
        }
    }

}