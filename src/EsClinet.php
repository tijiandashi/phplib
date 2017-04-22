<?php
/**
 * Created by PhpStorm.
 * User: liujun
 * Date: 2017/4/20
 * Time: 23:25
 */

namespace TJDS\Lib;

use ElasticSearch\Client;

class EsClinet
{
    private static $_instance;
    private function __construct(){
    }

    private function _clone(){
    }

    public static function getInstance( $config )
    {
        if( ! isset(self::$_instance[$config]) || self::$_instance[$config] == null) {
            self::$_instance[$config] = Client::connection($config);
        }
        return self::$_instance[$config];
    }

    public static function query($param, $config="http://127.0.0.1:9200/dashi/jigou")
    {
        $esClinet = self::getInstance($config);
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

    public static function get($id, $config="http://127.0.0.1:9200/dashi/jigou" )
    {
        $esClinet = self::getInstance($config);
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

