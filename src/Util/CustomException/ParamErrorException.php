<?php

namespace App\Util\CustomException;

use Exception;
use JetBrains\PhpStorm\Pure;

class ParamErrorException extends Exception
{
    //重写父类构造函数
    #[Pure] public function __construct($message, $code = 400) {
        return parent::__construct($message,$code);
    }
}