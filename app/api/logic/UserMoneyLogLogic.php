<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\ConsumeRecharge;
use app\common\model\user\User;
use app\common\model\user\UserAccountLog;
use app\common\model\UserMoneyLog;
use think\facade\Db;
use think\facade\Log;
use Exception;

/**
 * 用户金额记录逻辑层
 *
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class UserMoneyLogLogic extends BaseLogic
{
    /**
     * 统计
     *
     * @param $userId
     * @param $nUserIds
     * @param $startTime
     * @param $endTime
     * @return array|false
     */
    public static function groupSum($userId, $nUserIds, $startTime = null, $endTime = null)
    {
        try {

            $where = [
                ['change_type', '=', 1],
                ['type', '=', 3]
            ];

            $obj = UserMoneyLog::field([
                'n_user_id',
                "sum(`change_money`) as change_money"
            ])
                ->where($where)
                ->where('user_id', $userId)
                ->whereIn('n_user_id', $nUserIds);

            if (!empty($startTime) && !empty($endTime)) {
                $obj = $obj->whereBetween('create_time', [$startTime, $endTime]);
            }

            return $obj->group(['n_user_id'])
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-UserMoneyLogLogic-List Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }
}
