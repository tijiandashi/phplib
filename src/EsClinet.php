<?php
/**
 * Created by PhpStorm.
 * User: liujun
 * Date: 2017/4/20
 * Time: 23:25
 */

namespace TJDS\Phplib;

define('LIB_PATH', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';

use \ElasticSearch\Client;

class EsClinet
{
    private static $_instance;
    private function __construct(){
    }

    private function _clone(){
    }

    public static function getInstance( $key = "jigou")
    {
        if( ! isset(self::$_instance[$key]) || self::$_instance[$key] == null) {
            self::$_instance[$key] = Client::connection("http://127.0.0.1:9200/dashi/$key");
        }
        return self::$_instance[$key];
    }

    public static function query($param, $key="jigou")
    {
        $esClinet = self::getInstance($key);
        $ret = $esClinet->search($param);

        $output['total'] = $ret['hits']['total'];
        $output['list'] = [];
        if( $output['total'] > 0){
            foreach ($ret['hits']['hits'] as $item) {
                $output['list'] [] = $item['_source'];
            }
        }
        return $output;
    }

    public static function get($id, $key="jigou")
    {
        $esClinet = self::getInstance($key);
        $ret = $esClinet ->get($id);
        return $ret;
    }
}

/*

$param = [
	'match' => [
		'tags' => crc32("停车位"),
	],
];
$ret = \EsClinet::query([]);
var_dump($ret);
$ret = \EsClinet::get(3);
var_dump($ret);*/

