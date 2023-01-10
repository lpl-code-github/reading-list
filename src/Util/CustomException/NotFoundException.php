<?php

namespace App\Util\CustomException;

use Exception;
use JetBrains\PhpStorm\Pure;

class NotFoundException extends Exception
{
    //重写父类构造函数
    #[Pure] public function __construct($message, $code = 404)
    {
        return parent::__construct($message, $code);
    }
}