<?php
/**
 * Created by PhpStorm.
 * User: liujun
 * Date: 2017/4/20
 * Time: 23:19
 */
namespace TJDS\Phplib;

class Terminal{
    const UNKNOWN = 0;
    const PC = 1;
    const WAP = 2;
    const IOS = 3;
    const ANDROID = 4;

    public static function getClientTerminal() {
        //IOS oR WAP
        if( strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') )  {
            return isset($_GET['sign']) && strlen($_GET['sign']) > 0 ? self::IOS : self::WAP ;
        }

        // Android oR WAP
        if( strpos($_SERVER['HTTP_USER_AGENT'], 'Android')) {
            return isset($_GET['sign']) && strlen($_GET['sign']) > 0 ? self::ANDROID :  self::WAP ;
        }
        return self::PC;
    }
}