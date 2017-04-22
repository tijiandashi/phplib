<?php
/**
 * Created by PhpStorm.
 * User: liujun
 * Date: 2017/4/20
 * Time: 23:14
 */
namespace TJDS\Lib;

class PageBar {
    /**
     *
     * @param int $CURRENT_NUM 当前页起始数值
     * @param int $NUM_PER_PAGE 每页显示多少数目
     * @param int $PAGE_NUM_LIMIT 显示的页数
     * @param int $TOTAL_NUM 结果的总数目
     * @param int $TOTAL_NUM_LIMIT 页码最大显示数目
     * @return string page_list供前端展示
     */
    public static function showPageNum($CURRENT_NUM , $NUM_PER_PAGE , $PAGE_NUM_LIMIT = 10, $TOTAL_NUM, $TOTAL_NUM_LIMIT )
    {
        if ( $CURRENT_NUM < 0 || $TOTAL_NUM <= $NUM_PER_PAGE ) return false;
        if ( $TOTAL_NUM_LIMIT != 0 ) {
            $TOTAL_NUM =  ($TOTAL_NUM >= $TOTAL_NUM_LIMIT)? $TOTAL_NUM_LIMIT : $TOTAL_NUM;
        }
        $CURRENT_PAGE_NUM = intval( $CURRENT_NUM / $NUM_PER_PAGE );
        $TOTAL_PAGE_NUM = intval( $TOTAL_NUM / $NUM_PER_PAGE );
        if ( ($TOTAL_PAGE_NUM * $NUM_PER_PAGE) < $TOTAL_NUM ) {
            $TOTAL_PAGE_NUM++;
        }
        $URL = 'zhidao';
        $URL = (preg_match("/\?/",$URL))? $URL."&" : $URL."?";
        $output = "";
        $resArr = array();
        $head_offset = 4;     // 当前页向前偏移位置
        $tail_offset = 5;     // 当前页向后偏移位置
        // 总页数小于指定页数
        if ( $TOTAL_PAGE_NUM <= $PAGE_NUM_LIMIT ) {
            $start = 0;
            $end = $TOTAL_PAGE_NUM;
        } else {
            // 当前页靠前
            if ( $CURRENT_PAGE_NUM <= $head_offset) {
                $start = 0;
                $end = $start + $PAGE_NUM_LIMIT;
            }
            // 当前页靠后
            else if ( ($CURRENT_PAGE_NUM + $tail_offset) >= $TOTAL_PAGE_NUM) {
                $start = $TOTAL_PAGE_NUM - $PAGE_NUM_LIMIT;
                $end = $TOTAL_PAGE_NUM;
            }
            // 当前页在中间
            else {
                $start = $CURRENT_PAGE_NUM - $head_offset;
                $end = $start + $PAGE_NUM_LIMIT;
            }
            // 如果1消失了，需要显示首页
            if ( $CURRENT_PAGE_NUM > $head_offset ) {
                $output .= '<a href="'.$URL.'pn=0">[首页]</a>';
                $resArr[] = array(0,'首页',0);
            }
        }
        // 只要不是第一页，都显示上一页
        if ($start != $CURRENT_PAGE_NUM) {
            $output .= '<a href="'.$URL.'pn='.($CURRENT_NUM - $NUM_PER_PAGE).'">[上一页]</a>';
            $resArr[] = array($CURRENT_NUM - $NUM_PER_PAGE,'上一页',0);
        }
        // 显示页码
        for ($i = $start; $i < $end; $i++) {
            if ( $i == $CURRENT_PAGE_NUM ) {
                $output .= '<b>'.($i+1).'</b>';
                $resArr[] = array($i+1,$i+1,1);
            } else {
                $output .= '<a href="'.$URL.'pn='.($i * $NUM_PER_PAGE).'">['.($i+1).']</a>';
                $resArr[] = array($i*$NUM_PER_PAGE, $i+1, 0);
            }
        }
        // 只要不是最后一页，都显示下一页
        if (($CURRENT_PAGE_NUM + 1) != $TOTAL_PAGE_NUM) {
            $output .= '<a href="'.$URL.'pn='.($CURRENT_NUM + $NUM_PER_PAGE).'">[下一页]</a>';
            $resArr[] = array($CURRENT_NUM + $NUM_PER_PAGE, '下一页', 0);
        }

        if ( $TOTAL_PAGE_NUM > $PAGE_NUM_LIMIT ) {
            // 如果最后一个页面消失了，需要显示最后一页
            if (($CURRENT_PAGE_NUM + $tail_offset + 1) < $TOTAL_PAGE_NUM) {
                $output .= '<a href="'.$URL.'pn='.(($TOTAL_PAGE_NUM - 1) * $NUM_PER_PAGE).'">[尾页]</a>';
                $resArr[] = array(($TOTAL_PAGE_NUM - 1) * $NUM_PER_PAGE, '尾页', 0);
            }
        }
        return $resArr;
    }
}
