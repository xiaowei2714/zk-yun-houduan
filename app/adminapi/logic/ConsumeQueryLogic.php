<?php

namespace app\adminapi\logic;

use app\common\model\ConsumeQuery;
use app\common\logic\BaseLogic;
use think\facade\Log;
use think\Model;
use Exception;

/**
 * query逻辑
 * Class RechargeLogic
 * @package app\adminapi\logic
 */
class ConsumeQueryLogic extends BaseLogic
{
    /**
     * 汇总数据
     *
     * @param $createTime
     * @return ConsumeQuery|array|false|Model|null
     */
    public static function countSum($createTime = null)
    {
        try {
            $obj = ConsumeQuery::field([
                'sum(`pay_price`) sum',
                'count(*) as cou',
            ]);

            if (!empty($createTime)) {
                $obj = $obj->where('create_time', '>=', $createTime);
            }

            return $obj->find();

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 汇总数据
     *
     * @param $startTime
     * @return array|false
     */
    public static function getGroupSumByDay($startTime = null)
    {
        try {
            $obj = ConsumeQuery::field([
                "FROM_UNIXTIME(`create_time`, '%m/%d') date",
                'sum(pay_price) as sum'
            ]);

            if (!empty($startTime)) {
                $obj = $obj->where('create_time', '>=', $startTime);
            }

            return $obj->group('date')->select()->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-RechargeLogic-getSum Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

}
