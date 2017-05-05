<?php
/***************************************************************************
*
* Copyright (c) 2016 tijiandashi.com, Inc. All Rights Reserved
*
***************************************************************************/



/**
* file Sms.php
* author liujun(liujun@tijiandashi.com)
* date 2017-03-04 13:30
* brief: 
*
**/

namespace TJDS\Lib;

class Sms{

    public static function sendSms($config, $mobile,$code){
        $tkey      = date('YmdHis',time());
        $username  = $config['username'];
        $password  = $config['password'];
        $pwd       = md5(md5($password).$tkey);
        $uri       = $config['uri'];
        $content   = $config['content'];
        $content   = str_replace('{code}',$code,$content);
        $productid = $config['productid'];

        $data['username']  = $username;
        $data['password']  = $pwd;
        $data['tkey']      = $tkey;
        $data['mobile']    = $mobile;
        $data['content']   = $content;
        $data['productid'] = $productid;
        $res = \TJDS\Lib\HttpProxy::getInstance('sms');
        $result = $res->post($uri,$data);
        \TJDS\Lib\Log::debug(sprintf("%s %s, data[%s], result [%s]",
        	__CLASS__, __FUNCTION__, var_export($data, true), var_export($result, true) ));
        return $result;
    }
}
