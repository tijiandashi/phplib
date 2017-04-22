<?php
/**
 * Created by PhpStorm.
 * User: liujun
 * Date: 2017/4/22
 * Time: 下午8:15
 */

namespace TJDS\Lib;

class Request {

    public  static function getRequest($request, $key)
    {
        $value = $request->getQuery($key);
        if( $value == "") {
            $value = $request->getPost($key);
        }
        return $value;
    }

    public static function getHost($trim = false)
    {
        $host = $_SERVER['HTTP_HOST'];
        $hostArr = explode(".", $host);
        if (count($hostArr) == 3 && $trim == true) {
            array_shift($hostArr);
        }
        return $hostArr;
    }
}