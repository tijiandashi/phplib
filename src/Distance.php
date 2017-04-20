<?php
/**
 * Created by PhpStorm.
 * User: liujun
 * Date: 2017/4/20
 * Time: 23:23
 */
namespace TJDS\Phplib;

class Distance
{
    public static function distance($lat1, $lon1, $lat2,$lon2,$radius = 6378.137)
    {
        $rad = floatval(M_PI / 180.0);

        $lat1 = floatval($lat1) * $rad;
        $lon1 = floatval($lon1) * $rad;
        $lat2 = floatval($lat2) * $rad;
        $lon2 = floatval($lon2) * $rad;

        $theta = $lon2 - $lon1;

        $dist = acos(sin($lat1) * sin($lat2) +
            cos($lat1) * cos($lat2) * cos($theta)
        );

        if ($dist < 0 ) {
            $dist += M_PI;
        }
        return $dist = $dist * $radius;
    }
}