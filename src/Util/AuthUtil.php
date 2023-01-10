<?php

namespace App\Util;

use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class AuthUtil
{
    private UserRepository $userRepository;
    private $logger;

    public function __construct(UserRepository $userRepository, LoggerInterface $logger)
    {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }


    /**
     * 检查密码是否一致
     * @param $normalised
     * @param $checked
     * @return bool
     */
    // 检查密码是否正确
    public function checkPwd($checked, $hash): bool
    {
        return password_verify($checked, $hash);
    }

    /**
     * 密码加密
     * @param $pwd
     * @return string
     */
    public function encodedPwd($pwd): string
    {
        return password_hash($pwd, PASSWORD_DEFAULT);
    }


    /**
     * 创建token
     * @param string $username
     * @param string $pwd
     * @return string
     */
    public function encodedToken(string $username, int $userId): string
    {
        // 用户名和密码拼接
        $token = $username . '.' . $userId;
        // 转base64  返回token
        return base64_encode($token);
    }

    /**
     * 验证token
     * @param string $token
     * @return bool|User
     */
    public function verifyToken(string $token): bool|User
    {
        //base64解码
        $decodeToken = $this->decodeToken($token);

        //截取用户id 查库
        $userId = explode(".", $decodeToken)[1] ?? "";
        // 查库
        $user = $this->userRepository->findOneBy(['id' => $userId]);

        if ($user == null) {
            return false;
        };


        $this->logger->info("verifyToken userId : ".$userId);
        if ($this->encodedToken($user->getUsername(), $user->getId()) != $token) {
            return false;
        }
        return $user;
    }

    /**
     * 解码token
     * @param string $token
     * @return false|string
     */
    public function decodeToken(string $token): bool|string
    {
        return base64_decode($token);
    }


    /**
     * 调用此方法的前提是保证token存在
     * @param Request $request
     * @return int
     */
    public function getUserId(Request $request): int
    {
//        $headers = $request->headers->all();
//        $token = "";
//        if (isset($headers['authorization'])) {
//            $token = $headers['authorization'][0];
//        }
//        $this->logger->debug("token:" . $token);
//        $decodeToken = $this->decodeToken($token);

        $token = $request->query->get("token");
        $this->logger->debug("token:" . $token);
        $decodeToken = $this->decodeToken($token);
        return intval(explode(".", $decodeToken)[1] ?? "");
    }


    /**
     * 检查请求中是否存在token
     * @param Request $request
     * @return bool|User
     */
    public function checkRequestToken(Request $request): bool|User
    {
        $headers = $request->headers->all();
        if (array_key_exists('authorization', $headers)) {
            if (count($headers['authorization']) != 0) {
                $token = $headers['authorization'][0];
                return $this->verifyToken($token);
            }
        }
        return false;
    }
}