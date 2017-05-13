<?php
namespace TJDS\Lib;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class QiniuClient
{
    const AK= "uK5hYhgItfyK7FrStqgTX3W61S8c1ztohngHZfFS";
    const SK= "qWy-Bl9hMx3FwEv_PL8cpcMiuYzXlT_-UrXd5rQ-";

    public static function put($bucket, $upPath, $stream){
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = self::AK;
        $secretKey = self::SK;
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 生成上传 Token
        $token = $auth->uploadToken($bucket);

        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传
        return $uploadMgr->put($token, $upPath, $stream);

    }

    public static function putFile($bucket, $upPath, $filePath){
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = self::AK;
        $secretKey = self::SK;
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 生成上传 Token
        $token = $auth->uploadToken($bucket);

        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();

        return $uploadMgr->putFile($token, $upPath, $filePath);
    }
}