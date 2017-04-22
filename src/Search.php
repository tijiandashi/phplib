<?php
/**
 * Created by PhpStorm.
 * User: liujun
 * Date: 2017/4/20
 * Time: 23:22
 */

namespace TJDS\Lib;

class Search
{
    public static function geneType($types, $request)
    {
        $output = [];
        foreach($types as $key => $type) {
            $param = self::addKey(['price', 'area', 'sort'], $request);
            if( $key > 0) {
                $param[] = "type=".$key;
            }
            $output[] = [
                'checked' => intval($request['type']) === $key?  true : false,
                'url' => implode("&", $param),
                'name' => $type,
            ];
        }
        return $output;
    }

    public static function geneArea($areas, $request)
    {
        $output = [];
        foreach($areas as $aid => $area) {
            $param = self::addKey(['type', 'price', 'sort', 'tag'], $request);
            if( $aid > 0) {
                $param[] = "area=".$aid;
            }
            $output[] = [
                'checked' => intval($request['area']) === $aid?  true : false,
                'url' => implode("&", $param),
                'name' => $area,
            ];
        }
        return $output;
    }

    public static function genePrice($prices, $request)
    {
        foreach($prices as $key => $price){
            $param = self::addKey(['type', 'area', 'sort', 'tag'], $request);
            if( $price['url'] != "0") {
                $param[] = "price=".$price['url'];
            }

            if( !isset( $request['price']) ) {
                $request['price'] = "0";
            }

            $output[] = [
                'checked' => $request['price'] === $price['url']?  true : false,
                'url' => implode("&", $param),
                'name' => $price['name'],
            ];
        }
        return $output;
    }

    public static function geneTags($tags, $request)
    {
        foreach($tags as $key => $tag){
            $param = self::addKey(['price', 'area', 'sort', 'tag'], $request);
            if(! isset($request['tag'])){
                $request['tag'] = 0;
            }
            $param[] = "tag=".$key;
            $output[] = [
                'checked' => intval($request['tag']) === $key?  true : false,
                'url' => implode("&", $param),
                'name' => $tag,
            ];
        }
        return $output;
    }

    public static function geneFilter($filters, $request)
    {
        foreach ($filters as $key => $sort) {
            $param = self::addKey(['type', 'area', 'price', 'tag'], $request);
            if( $key != 0) {
                $param[] = "sort=".$key;
            }
            $output[] = [
                'checked' => intval($request['sort']) === intval($key) ?  true : false,
                'url' => implode("&", $param),
                'name' => $sort['name'],
            ];
        }
        return $output;
    }

    public static function genSelect($request, $types, $areas, $prices, $tags)
    {
        $selects = [];
        if( intval($request['type']) > 0 ){
            $tmpSel['name'] = $types[ intval($request['type']) ];
            $param = self::addKey(['sort', 'area', 'price', 'tag'], $request);
            $tmpSel['url'] = implode("&", $param);
            $selects[] = $tmpSel;
        }
        if( trim($request['price']) != ""){
            $tmpSel['name'] = $request['price'];
            $param = self::addKey(['sort', 'area', 'type', 'tag'], $request);
            $tmpSel['url'] = implode("&", $param);
            $selects[] = $tmpSel;
        }
        if( intval($request['area']) > 0 ){
            $tmpSel['name'] = $areas[ intval($request['area']) ];
            $param = self::addKey(['sort', 'type', 'price', 'tag'], $request);
            $tmpSel['url'] = implode("&", $param);
            $selects[] = $tmpSel;
        }
        if( isset($request['tag']) && intval($request['tag']) > 0 ){
            $tmpSel['name'] = $tags[ intval($request['tag']) ];
            $param = self::addKey(['area', 'type', 'price', 'tag'], $request);
            $tmpSel['url'] = implode("&", $param);
            $selects[] = $tmpSel;
        }
        return $selects;
    }

    public static function addKey($keys, $request){
        $output = [];
        foreach ($keys as $key ) {
            if( isset($request[$key]) && ( trim($request[$key]) != "" || intval($request[$key]) > 0) ) {
                $output[] = "$key=".$request[$key];
            }
        }
        return $output;
    }

    public static function filterSearchJigou($jigous, $areas){
        $output = [];
        //echo json_encode($jigous); exit;
        foreach($jigous as $jigou){
            $output[] = [
                'name' => $jigou['name'],
                'ename' => $jigou['ename'],
                'areaName' => $areas[$jigou['areaid']],
                'cover' =>  $jigou['cover'],
                'address' =>  $jigou['address'],
                'opentime' =>  $jigou['opentime'],
                'minPrice' =>  $jigou['minPrice'],
                'taocanCnt' =>  $jigou['taocanCnt'],
                'commentCnt' =>  $jigou['commentCnt'],
                'typeName' => \Dashi\config\Common::$types[ $jigou['type']],
            ];
        }
        return $output;
    }

    public static function filterSearchTaocan($taocans, $areas){
        $output = [];
        foreach($taocans as $taocan){
            $output[] = [
                'name' => $taocan['name'],
                'ename' => $taocan['ename'],
                'areaName' => $areas[$taocan['areaid']],
                'cover' =>  $taocan['cover'],
                'jname' =>  $taocan['jname'],
                'jename' =>  $taocan['jename'],
                'saleNum' =>  $taocan['sale_num'],
                'price' =>  $taocan['price'],
                'oriPrice' =>  $taocan['ori_price'],
            ];
        }
        return $output;
    }

    public static function getNear($data, $key="dashi")
    {
        $param = [
            'from' => $data['pn'],
            'size' => $data['rn'],
        ];

        $param['sort'] = array(
            "_geo_distance" => array(
                'location' => array(
                    'lat' => doubleval($data['geo']['lat']),
                    'lon' => doubleval($data['geo']['lng']),
                ),
                "order"  => 'asc',
                "unit" => "m",
            ),
        );

        $result = \Dashi\Library\EsClinet::query($param, $key);
        return $result;
    }

    public static function getScore($data, $key="dashi")
    {
        $param = [
            'from' => $data['pn'],
            'size' => $data['rn'],
        ];

        $param['sort']['score'] = [
            'order' => 'desc',
        ];

        $result = \Dashi\Library\EsClinet::query($param, $key);
        return $result;
    }

    public static function getSort($data, $sortKey, $sortType="desc", $key="dashi")
    {
        $param = [
            'from' => $data['pn'],
            'size' => $data['rn'],
        ];

        $param['sort'][$sortKey] = [
            'order' => $sortType,
        ];

        $result = \Dashi\Library\EsClinet::query($param, $key);
        return $result;
    }
}
