<?php
/**
 * Created by PhpStorm.
 * User: liujun
 * Date: 2017/4/22
 * Time: 下午4:40
 */
namespace TJDS\Phplib;

class LibException extends \Exception
{
    CONST OK = 0;
    CONST INTER_ERR = 1;
    CONST SYS_ERR = 2;

    public static $msgs = [
        self::OK => '正确',
        self::INTER_ERR => '内部错误',
        self::SYS_ERR => '系统错误',
    ];

    public function __construct($code=0, $string=""){
        $this->code = $code;
        $this->message = sprintf(self::$msgs[$code], $string);
    }
}