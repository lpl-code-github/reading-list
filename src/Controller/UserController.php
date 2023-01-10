<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Util\AuthUtil;
use App\Util\CustomException\NotFoundException;
use App\Util\CustomException\ParamErrorException;
use App\Util\Result;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private AuthUtil $authUtil;

    public function __construct(AuthUtil $authUtil)
    {
        $this->authUtil = $authUtil;
    }

    /**
     * 登录
     * @throws NotFoundException
     * @throws ParamErrorException
     */
    #[Route('/login', name: 'user_login', methods: "POST")]
    public function login(Request $request, UserRepository $userRepository): JsonResponse
    {
        // 获取参数
        try {
            $resource = $request->toArray();
            $username = $resource["username"];
            $pwd = $resource["pwd"];

        } catch (\Exception $e) {
            throw new ParamErrorException('Missing parameter or parameter format is not JSON');
        }

        // 入参校验
        $this->checkParam($username, $pwd);

        // 查库，查看用户是否存在
        $user = $userRepository->findOneBy(['username' => $username]);
        if ($user == null) {
            throw new NotFoundException("user does not exist");
        }

        $result = new Result();

        // 校验密码
        if (!$this->authUtil->checkPwd($pwd, $user->getPwd())) {
            throw new NotFoundException("Password error");
        }

        // 创建token 存入session 返回token
        $token = $this->authUtil->encodedToken($username, $user->getId());
        $session = $request->getSession();
        $session->set('token', $token);
        return $this->json($result->success(
            array(
                "token" => $token
            )
        ));
    }

    /**
     * 注册
     * @param Request $request
     * @param UserRepository $userRepository
     * @return JsonResponse
     * @throws ParamErrorException
     */
    #[Route('/register', name: 'user_register', methods: "POST")]
    public function register(Request $request, UserRepository $userRepository): JsonResponse
    {
        // 获取参数
        try {
            $resource = $request->toArray();
            $username = $resource["username"];
            $pwd = $resource["pwd"];
            $repeatPwd = $resource["repeatPwd"];
            $email = $resource["email"];
        } catch (\Exception $e) {
            throw new ParamErrorException('Missing parameter or parameter format is not JSON');
        }

        // 入参校验
        if ($pwd !== $repeatPwd) {
            throw new ParamErrorException('The passwords entered twice are inconsistent');
        }
        $this->checkParam($username, $pwd, $email);

        // 查库
        $user = $userRepository->findOneBy(['username' => $username]);
        if ($user != null) {
            throw new ParamErrorException("The user already exists");
        }

        // 落库
        $user = new User();
        $user->setUsername($username);
        $user->setPwd($this->authUtil->encodedPwd($pwd));// 密码加密存储
        $user->setEmail($email);
        $result = new Result();
        return $this->json($result->success($userRepository->save($user, true)));
    }

    /**
     * 登出
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/log_out', name: 'user_logout', methods: "GET")]
    public function logOut(Request $request): JsonResponse
    {
        // 获取参数
        $session = $request->getSession();
        $session->remove('token');//清空session中的token
        $result = new Result();
        return $this->json($result->success(true));
    }


    /**
     * 参数校验
     * @param string $username
     * @param string $pwd
     * @param string $email
     * @return void
     * @throws ParamErrorException
     */
    private function checkParam(string $username, string $pwd, string $email = "")
    {
        // 参数校验
        if (empty($username) || empty($pwd)) {
            throw new ParamErrorException('parameter error');
        }
        if (mb_strlen($username) > 20) {
            throw new ParamErrorException('username parameter error');
        }
        if (mb_strlen($pwd) > 10) {
            throw new ParamErrorException('pwd parameter error');
        }
        if (!empty($email) && mb_strlen($email) > 40) {
            throw new ParamErrorException('email parameter error');
        }
    }
}
