<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\Substation;
use think\facade\Log;
use Exception;

/**
 * 分站者充值逻辑层
 *
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class SubstationLogic extends BaseLogic
{
    /**
     * 详情
     *
     * @param $userId
     * @return array|false
     */
    public static function info($userId)
    {
        try {
            return Substation::where('user_id', '=', $userId)->find();
        } catch (Exception $e) {
            Log::record('Exception: Sql-SubstationLogic-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }
}
