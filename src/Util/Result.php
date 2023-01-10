<?php
/*
 * 接口统一返回值
 */

namespace App\Util;


class Result
{
    var $code;
    var $status;
    var $time;
    var $data;

    /**
     * @param mixed $code
     */
    public function setCode($code): void
    {
        $this->code = $code;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @param mixed $time
     */
    public function setTime($time): void
    {
        $this->time = $time;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }


    /**
     * @param $statusCode
     * @return string
     * @desc 内部方法，获得httpCode对应的状态信息
     */
    private function getHttpStatusMessage($statusCode): string
    {
        $httpStatus = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported');
        return $httpStatus[$statusCode];
    }


    public function success($data): Result
    {
        $this->setCode(200);
        $this->setStatus($this->getHttpStatusMessage(200));
        $this->setTime(time());
        $this->setData($data);
        return $this;
    }

    public function fail($code,$data,$status): Result
    {
        $this->setCode($code);
        $this->setStatus(empty($status)?$this->getHttpStatusMessage($code):$status);
        $this->setTime(time());
        $this->setData($data);
        return $this;
    }

    public function serverError(string $message): Result
    {
        $this->setCode(500);
        $this->setStatus($this->getHttpStatusMessage(500).": ".$message);
        $this->setTime(time());
        $this->setData(null);
        return $this;
    }

    public function paramError(string $message): Result
    {
        $this->setCode(400);
        $this->setStatus($this->getHttpStatusMessage(400).": ".$message);
        $this->setTime(time());
        $this->setData(null);
        return $this;
    }

    public function notFound($message): Result
    {
        $this->setCode(404);
        $this->setStatus($this->getHttpStatusMessage(404).": ".$message);
        $this->setTime(time());
        $this->setData(null);
        return $this;
    }

    public function noPermission($message):Result
    {
        $this->setCode(401);
        $this->setStatus($this->getHttpStatusMessage(401).": ".$message);
        $this->setTime(time());
        $this->setData(null);
        return $this;
    }


    public function __toString(){
        return json_encode($this);
    }
}