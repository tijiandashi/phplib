<?php
/**
 * Created by PhpStorm.
 * User: liujun
 * Date: 2017/4/20
 * Time: 23:19
 */

namespace TJDS\Lib;

class Trace
{
    private static $_instance = null;
    private static $_docTrace = [];
    public static $debug = 0;

    public function __construct()
    {
        self::$_docTrace = [];
        self::$debug = 0;
    }

    public static function getInstance()
    {
        if( self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function setDebug($debug)
    {
        self::$debug = $debug;
    }

    private static function _getStr($str, $paramArr, $resArr, $depth=0)
    {
        $trace = debug_backtrace();
        if( $depth >= count($trace) ) {
            $depth = count($trace) - 1;
        }
        $file =  $trace[$depth]['file'];
        $line = $trace[$depth]['line'];

        $output = [
            'file' => $file.":".$line,
            'msg' => $str,
        ];

        if( !empty($paramArr)){
            if( isset($paramArr['remoteServer'])) {
                $output ['server'] = $paramArr['remoteServer'];
                unset( $paramArr['remoteServer'] );
            }
            $output ['param'] = $paramArr;
        }

        if( !empty($resArr)){
            $output ['result'] = $resArr;
        }

        return $output;
    }

    public function Add($str="", $paramArr=[], $resArr=[], $level = 1 )
    {
        if( self::getValid() == false) {
            return false;
        }
        $md5 = md5($str);
        $dataArr =  self::_getStr($str, $paramArr, $resArr, $level);
        self::$_docTrace[$md5] = $dataArr;
    }

    public  function Attach($str, $resArr=[] )
    {
        if( self::checkVaild() == false) {
            return false;
        }

        $data = json_decode($resArr, true);
        if( json_last_error() == JSON_ERROR_NONE) {
            $resArr = $data;
        }

        $md5 = md5($str);
        if( $str != "" &&  isset(self::$_docTrace[$md5]) ) {
            self::$_docTrace[$md5]['result'] = $resArr;
        }
    }

    public function getTrace()
    {
        if( self::getValid() == 0) {
            return false;
        }
        return array_values(self::$_docTrace);
    }

    public static function getValid()
    {
        return self::$debug;
    }
}
